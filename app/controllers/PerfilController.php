<?php

/**
 * 
 */
class PerfilController extends \HXPHP\System\Controller
{
	public function __construct($configs)
	{
		parent::__construct($configs);

		$this->load(
			'Services\Auth',
			$configs->auth->after_login,
			$configs->auth->after_logout,
			true
		);

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		$this->view->setFile('editar')
				->setTitle('SIDE | Editar perfil')
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTemplate(true)
				->setVar('user', User::find($user_id));
	}

	public function editarAction() 
	{
		$this->view->setFile('editar');

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		$this->request->setCustomFilters(array(
			'email' => FILTER_VALIDATE_EMAIL
		));
		
		$post = $this->request->post();

		if(!empty($post)) {
			$atualizarUsuario = User::atualizar($user_id, $post);
			
			if ($atualizarUsuario->status === false) {
				$this->load('Helpers\Alert', array(
					'error',
					'Não foi possível atualizar seu perfil. Verifique os erros abaixos:',
					$atualizarUsuario->errors
				));
			}
			else {
				if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
					$uploadUserImage = new upload($_FILES['image']);

					if ($uploadUserImage->uploaded) {
						$image_name = md5(uniqid());

						$uploadUserImage->file_new_name_body = $image_name;
						$uploadUserImage->file_new_name_ext = 'png';
						$uploadUserImage->resize = true;
						$uploadUserImage->image_x = 200;
						$uploadUserImage->image_ratio_y = true;

						$dir_path = ROOT_PATH . DS . 'public' . DS . 'uploads' . DS . 'users' . DS . $atualizarUsuario->user->id . DS;
						$uploadUserImage->Process($dir_path);

						if ($uploadUserImage->processed) {
							$uploadUserImage->Clean();
							$this->load('Helpers\Alert', array(
								'success',
								'Perfil atualizado com sucesso.'
							));

							if (!is_null($atualizarUsuario->user->image)) {
								unlink($dir_path . $atualizarUsuario->user->image);
							}

							$atualizarUsuario->user->image = $image_name . '.png';

							$atualizarUsuario->user->save(false);
						}
						else {
							$this->load('Helpers\Alert', array(
								'error',
								'Ops! Não foi possível atualizar sua imagem de perfil.',
								$uploadUserImage->error
							));
						}
					}
				}
				else {
					$this->load('Helpers\Alert', array(
						'success',
						'Uhull! Perfil atualizado com sucesso.'
					));
				}

				$this->view->setVar('user', $atualizarUsuario->user);
				
			}
		}
	}
}