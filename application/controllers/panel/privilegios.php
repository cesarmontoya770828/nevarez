<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class privilegios extends MY_Controller {
	
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
		if($this->empleados_model->checkSession()){
			$this->info_empleado = $this->empleados_model->getInfoEmpleado($_SESSION['id_empleado'], true);
			if($this->empleados_model->tienePrivilegioDe('', get_class($this).'/'.$method.'/')){
				$this->{$method}();
			}else
				redirect(base_url('panel/home?msg=1'));
		}else
			redirect(base_url('panel/home'));
	}
	
	/**
	 * Default. Mustra el listado de privilegios para administrarlos
	 */
	public function index(){
		$this->carabiner->css(array(
			array('libs/jquery.msgbox.css', 'screen'),
			array('general/forms.css', 'screen'),
			array('general/tables.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.msgbox.min.js'),
			array('general/msgbox.js')
		));
		$this->load->library('pagination');
		
		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['opcmenu_active'] = 'Privilegios'; //activa la opcion del menu
		$params['seo'] = array(
			'titulo' => 'Administrar privilegios'
		);
		
		$params['privilegios'] = $this->empleados_model->obtenPrivilegios();
		
		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		
		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/privilegios/listado', $params);
		$this->load->view('panel/footer');
	}
	
	/**
	 * Agrega un privilegio a la bd
	 */
	public function agregar(){
		$this->carabiner->css(array(
			array('general/forms.css', 'screen')
		));
		
		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['opcmenu_active'] = 'Privilegios'; //activa la opcion del menu
		$params['seo'] = array(
			'titulo' => 'Agregar privilegio'
		);
		
		$this->configAddModPriv();
		
		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}else{
			$respons = $this->empleados_model->addPrivilegio();
			
			if($respons[0])
				redirect(base_url('panel/privilegios/agregar/?'.String::getVarsLink(array('msg')).'&msg=4'));
		}
		
		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		
		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/privilegios/agregar', $params);
		$this->load->view('panel/footer');
	}
	
	/**
	 * carga el login para entrar al panel
	 */
	public function modificar(){
		$this->carabiner->css(array(
				array('general/forms.css', 'screen')
		));
		
		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['opcmenu_active'] = 'Privilegios'; //activa la opcion del menu
		$params['seo'] = array(
			'titulo' => 'Modificar privilegio'
		);
		
		if(isset($_GET['id']{0})){
			$this->configAddModPriv();
			
			if($this->form_validation->run() == FALSE){
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}else{
				$respons = $this->empleados_model->updatePrivilegio();
				
				if($respons[0])
					redirect(base_url('panel/privilegios/modificar/?'.String::getVarsLink(array('msg')).'&msg=3'));
			}
			
			$params['privilegio'] = $this->empleados_model->getInfoPrivilegio($_GET['id']);
			if(!is_object($params['privilegio']))
				unset($params['privilegio']);
			
			if(isset($_GET['msg']{0}))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		}else
			$params['frm_errors'] = $this->showMsgs(1);
		
		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/privilegios/modificar', $params);
		$this->load->view('panel/footer');
	}
	
	/**
	 * Elimina un privilegio de la bd
	 */
	public function eliminar(){
		if(isset($_GET['id']{0})){
			$respons = $this->empleados_model->deletePrivilegio();
			
			if($respons[0])
				redirect(base_url('panel/privilegios/?msg=5'));
		}else
			$params['frm_errors'] = $this->showMsgs(1);
	}
	
	
	/**
	 * Configura los metodos de agregar y modificar
	 */
	private function configAddModPriv(){
		$this->load->library('form_validation');
		$rules = array(
			array('field'	=> 'dnombre',
					'label'		=> 'Nombre',
					'rules'		=> 'required|max_length[100]'),
			array('field'	=> 'durl_accion',
					'label'		=> 'Url accion',
					'rules'		=> 'required|max_length[100]'),
			array('field'	=> 'durl_icono',
					'label'		=> 'Url icono',
					'rules'		=> 'max_length[100]'),
			array('field'	=> 'dmostrar_menu',
					'label'		=> 'Mostrar menu',
					'rules'		=> ''),
			array('field'	=> 'dtarget_blank',
					'label'		=> 'Target blank',
					'rules'		=> '')
		);
		$this->form_validation->set_rules($rules);
	}
	
	/**
	 * Muestra mensajes cuando se realiza alguna accion
	 * @param unknown_type $tipo
	 * @param unknown_type $msg
	 * @param unknown_type $title
	 */
	private function showMsgs($tipo, $msg='', $title='Privilegio!'){
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
				$txt = 'El privilegio se modifico correctamente.';
				$icono = 'ok';
			break;
			case 4:
				$txt = 'El privilegio se agrego correctamente.';
				$icono = 'ok';
			break;
			case 5:
				$txt = 'El privilegio se elimino correctamente.';
				$icono = 'ok';
			break;
		}
		
		return array(
			'title' => $title,
			'msg' => $txt,
			'ico' => $icono);
	}
}

?>