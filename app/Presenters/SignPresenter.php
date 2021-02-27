<?php
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


class SignPresenter extends Nette\Application\UI\Presenter
{
    private $database;
    private $passwords;

	public function __construct(Nette\Database\Explorer $database, \Nette\Security\Passwords $passwords)
	{
        $this->database = $database;
        $this->passwords = $passwords;
	}
	protected function createComponentSignInForm(): Form
	{
		$form = new Form;
		$form->addText('username', 'Username:')
			->setRequired('Please enter your username.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please enter your password.');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
    }
    protected function createComponentRegisterForm(): Form
	{
		$form = new Form;
		$form->addText('login', 'Username:')
			->setRequired('Please enter your username.');

        $password = $form->addPassword('password', 'Password:')
            ->setRequired('Please enter your password.');
        
        $form->addPassword('pwd2', 'Password (verify)')->setRequired('Please enter password for verification')->addRule($form::EQUAL, 'Password verification failed. Passwords do not match', $password);

		$form->addSubmit('send', 'Register');

		$form->onSuccess[] = [$this, 'registerFormSucceeded'];
		return $form;
    }
    public function registerFormSucceeded(Form $form, \stdClass $values): void
{
	try {
		$row = $this->database->table('users')
			->where('username', $values->login)
			->fetch();

		if($row)
			throw new Nette\Security\AuthenticationException('Username Exists.');
		else {
        $this->database->table('users')->insert([
            'username' => $values->login,
            'password' => $this->passwords->hash($values->password),
        ]); }
		$this->redirect('Homepage:');

	} catch (Nette\Security\AuthenticationException $e) {
		$form->addError('Incorrect username or password.');
	}
}
public function postFormSucceeded(Form $form, array $values): void
{
    if (!$this->getUser()->isLoggedIn()) {
		$this->error('You need to log in to create or edit posts');
	}
	$postId = $this->getParameter('postId');

	if ($postId) {
		$post = $this->database->table('posts')->get($postId);
		$post->update($values);
	} else {

        $values['user_id']= $this->getUser()->id;
		$post = $this->database->table('posts')->insert($values);
	}

	$this->flashMessage('Post was published', 'success');
	$this->redirect('show', $post->id);
}

    
    public function signInFormSucceeded(Form $form, \stdClass $values): void
{
	try {
		$this->getUser()->login($values->username, $values->password);
		$this->redirect('Homepage:');

	} catch (Nette\Security\AuthenticationException $e) {
		$form->addError('Incorrect username or password.');
	}
}
public function actionOut(): void
{
	$this->getUser()->logout();
	$this->flashMessage('You have been signed out.');
	$this->redirect('Homepage:');
}

}
