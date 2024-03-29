<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class asistencia extends MY_Controller {
	
	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('asistencia/enroll/', 'aviones/pdf_hva/');
	private $excepcion_session = array('enroll', 'tomar_asistencia', 'identify');
	
	public function _remap($method){
		$this->carabiner->css(array(
				array('libs/jquery-ui.css', 'screen'),
				array('libs/ui.notify.css', 'screen'),
				array('libs/jquery.treeview.css', 'screen'),
				array('base.css', 'screen')
		));
		$this->carabiner->js(array(
				array('libs/jquery.min.js'),
				array('libs/jquery-ui.js'),
				array('libs/jquery.notify.min.js'),
				array('libs/jquery.treeview.js'),
				array('general/alertas.js')
		));
		

		$this->load->model("empleados_model");
		if (array_search($method, $this->excepcion_session) !== false) {
			$this->{$method}();
		}elseif($this->empleados_model->checkSession()){
			$this->empleados_model->excepcion_privilegio = $this->excepcion_privilegio;
			$this->info_empleado = $this->empleados_model->getInfoEmpleado($_SESSION['id_empleado'], true);
			if($this->empleados_model->tienePrivilegioDe('', get_class($this).'/'.$method.'/')){
				$this->{$method}();
			}else
				redirect(base_url('panel/home?msg=1'));
		}else
			redirect(base_url('panel/home'));
	}
	
	private function index(){
		$this->carabiner->css(array(
					array('libs/jquery.msgbox.css','screen'),
					array('libs/jquery.superbox.css','screen'),
					array('general/forms.css','screen'),
					array('general/tables.css','screen')
				));
		
		$this->carabiner->js(array(
					array('libs/jquery.msgbox.min.js'),
					array('libs/jquery.superbox.js'),
					array('general/msgbox.js')
					));
		
		$this->load->library('pagination');
		
		$params['info_empleado']	= $this->info_empleado['info'];
		$params['opcmenu_active']	= 'Aviones'; //activa la opcion del menu
		$params['seo']				= array('titulo' => 'Administrar Aviones');
		
		$params['datos_a'] = $this->aviones_model->getAviones();
		
		if(isset($_GET['msg']))
		{
			$msg = ($_GET['msg']==2) ? 'El Avión no existe' : '';
			$params['frm_errors']	= $this->showMsgs($_GET['msg'],$msg);
		}
		
		$this->load->view('panel/header',$params);
		$this->load->view('panel/general/menu',$params);
		$this->load->view('panel/aviones/admin',$params);
		$this->load->view('panel/footer',$params);
	}
	
	private function registrar_huella(){
		$this->carabiner->css(array(
				array('general/forms.css','screen'),
				array('general/tables.css','screen')
		));
		
		$this->carabiner->js(array(
				array('asistencia/GrFinger.js')
		));
		
		$params['info_empleado']	= $this->info_empleado['info'];
		$params['opcmenu_active'] = 'Recursos Humanos'; //activa la opcion del menu
		$params['seo']	= array('titulo' => 'Registrar Huella');
		
		if(isset($_GET['msg']{0}))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		
		$this->load->view('panel/header',$params);
		$this->load->view('panel/general/menu',$params);
		$this->load->view('panel/asistencia/registrar_huella',$params);
		$this->load->view('panel/footer',$params);
	}

	private function enroll(){
		// Including the class GrFingerService
		$this->load->library('GrFingerService');
		// Check if template is empty
		if(!isset($_POST['tpt']) || !isset($_POST['id'])){
			echo "{ret:0,id:0,msg:'El template y el id son campos requeridos.',icon:'error'}";
			exit;
		}
		$grs = new GrFingerService();
		// Calling the application startup code
		if($grs->initialize())
		{
			// Posting the template to be enrolled
			$id = $grs->enroll($_POST['tpt'], $_POST['id'], $this->input->post('huella'));
			// Returning the result of the enroll process
			echo "{ret:0,id:".$id.",msg:'Se registro correctamente la huella.',icon:'ok'}";
			// Calling the finalization code
			$grs->finalize();
		}
	}

	private function tomar_asistencia(){
		$this->carabiner->css(array(
				array('general/forms.css','screen'),
				array('general/tables.css','screen')
		));
		
		$this->carabiner->js(array(
				array('asistencia/GrFinger.js')
		));

		//$this->info_empleado = $this->empleados_model->getInfoEmpleado($_SESSION['id_empleado'], true);
		
		//$params['info_empleado']	= $this->info_empleado['info'];
		$params['opcmenu_active'] = 'Recursos Humanos'; //activa la opcion del menu
		$params['seo']	= array('titulo' => 'Tomar asistencia');
		
		if(isset($_GET['msg']{0}))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		
		$this->load->view('panel/asistencia/tomar_asistencia',$params);
	}

	private function identify(){
		// Including the class GrFingerService
		$this->load->library('GrFingerService');
		
		if(!isset($_POST['tpt'])){
			echo "{ret:0,id:0,msg:'El template es requeridos.',icon:'error'}";
			exit;
		}
		$grs = new GrFingerService();
		// Calling the application startup code
		if($grs->initialize())
		{
			// Posting the template to be identified		
			$data = $grs->identify($_POST['tpt']);
			// Returning the result of the identification process
			if(is_array($data)){
				$dres = $this->db->query("SELECT registra_asistencia('".$data['id_empleado']."', '".$data['hora_entrada']."') AS res; ")->row();

				$img = $data['url_img']==''? base_url('application/images/empleados/general.jpg'): base_url($data['url_img']);
				if ($dres->res == 1) {
					echo "{ret:1,id:'".$data['apellido_paterno']." ".$data['nombre']."',img:'".$img."',
					msg:'Se registro correctamente la asistencia.',icon:'ok'}";
				}else
					echo "{ret:1,id:'".$data['apellido_paterno']." ".$data['nombre']."',img:'".$img."',
					msg:'Se registro la salida correctamente.',icon:'ok'}";
			}else	
				echo "{ret:".$data.",id:0,msg:'No se encontro el trabajador.',icon:'alert'}";
			// Calling the finalization code
			$grs->finalize();
		}
	}







	
	private function modificar(){
		
		if(isset($_GET['id']) && $this->aviones_model->exist('aviones',array('id_avion'=>$_GET['id'],'status'=>'ac')))
		{
			$this->carabiner->css(array(
					array('general/forms.css','screen'),
					array('general/tables.css','screen')
			));
			
			$this->carabiner->js(array(
					array('aviones/frm_addmod.js')
			));
			
			$this->configAddAvion();
			
			if($this->form_validation->run() == FALSE)
			{
				$params['frm_errors']	= $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$model_resp	= $this->aviones_model->editAvion($_GET['id']);
				if($model_resp[0])
					$params['frm_errors']	= $this->showMsgs(3);
				else
					$params['frm_errors']	= $this->showMsgs(2,'El avión que desea modificar no existe');
			}
			
			$params['info_empleado']	= $this->info_empleado['info'];
			$params['opcmenu_active'] 	= 'Aviones'; //activa la opcion del menu
			$params['seo']['titulo']	= 'Modificar Avion';
			$params = array_merge($params,$this->aviones_model->getAviones($_GET['id']));
			
			if(isset($_GET['msg']{0}))
					$params['frm_errors'] = $this->showMsgs($_GET['msg']);
			
				$this->load->view('panel/header',$params);
				$this->load->view('panel/general/menu',$params);
				$this->load->view('panel/aviones/modificar',$params);
				$this->load->view('panel/footer',$params);
		}
		else
			redirect(base_url('panel/aviones/'));
	}
	
	private function eliminar(){
		if(isset($_GET['id']))
		{
			$result_model = $this->aviones_model->delAvion($_GET['id']);
			if($result_model[0])
				redirect(base_url('panel/aviones/?&msg=5'));
			else
				redirect(base_url('panel/aviones/?&msg=2'));
		}
	}

	public function hva()
	{
		$this->carabiner->css(array(
				array('general/forms.css', 'screen')
		));
		$this->carabiner->js(array(
				array('aviones/reporte_hva.js')
		));

		if (!isset($_GET['dfecha1'])) {
			$_GET['dfecha1'] = date('Y-m').'-01';
		}

		if (!isset($_GET['dfecha2'])) {
			$_GET['dfecha2'] = date('Y-m-d');
		}

		$params['seo'] = array(
				'titulo' => 'Reporte Horas de vuelo por avión'
		);

		$this->load->view('panel/aviones/reporte_hva', $params);
	}

	public function pdf_hva()
	{
		$this->load->model('aviones_model');
		$data = $this->aviones_model->data_hva();

		// var_dump($data);
		$this->aviones_model->pdf_hva($data);
	}

	/**
	 * Obtiene lostado de aviones para el autocomplete, ajax
	 */
	public function ajax_get_aviones(){
		$this->load->model('aviones_model');
		$params = $this->aviones_model->getAvionesAjax();
	
		echo json_encode($params);
	}
	
	
	private function configAddAvion(){
		$this->load->library('form_validation');
		
		$rules = array(
						array('field'	=> 'fmatricula',
								'label'	=> 'Matricula',
							  	'rules'	=> 'required|max_lenght[20]'),
						array('field'	=> 'fmodelo',
								'label'	=> 'Modelo',
								'rules'	=> 'max_lenght[10]'),
						array('field'	=> 'ftipo',
								'label'	=> 'tipo',
								'rules'	=> 'max_lenght[10]'),
						array('field'	=> 'dfecha_vence_tarjeta',
								'label'		=> 'Fecha vecimiento tarjeta',
								'rules'		=> 'required|max_length[10]|callback_isValidDate'),
						array('field'	=> 'dfecha_vence_seguro',
								'label'		=> 'Fecha vecimiento seguro',
								'rules'		=> 'required|max_length[10]|callback_isValidDate')
				);
		$this->form_validation->set_rules($rules);
	}
	
	/**
	 * Form_validation: Valida su una fecha esta en formato correcto
	 */
	public function isValidDate($str){
		if($str != ''){
			if(String::isValidDate($str) == false){
				$this->form_validation->set_message('isValidDate', 'El campo %s no es una fecha valida');
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Muestra mensajes cuando se realiza alguna accion
	 * @param unknown_type $tipo
	 * @param unknown_type $msg
	 * @param unknown_type $title
	 */
	private function showMsgs($tipo, $msg='', $title='Aviones!'){
		switch($tipo){
			case 1:
				$txt = 'El campo ID es requerido.';
				$icono = 'error';
				break;
			case 2: //Cuendo se valida con form_validation
				$txt = $msg;
				$icono = 'error';
				break;
			case 3:
				$txt = 'El Avión se modifico correctamente.';
				$icono = 'ok';
				break;
			case 4:
				$txt = 'El Avión se agrego correctamente.';
				$icono = 'ok';
				break;
			case 5:
				$txt = 'El Avión se elimino correctamente.';
				$icono = 'ok';
				break;
		}
	
		return array(
				'title' => $title,
				'msg' => $txt,
				'ico' => $icono);
	}	
}