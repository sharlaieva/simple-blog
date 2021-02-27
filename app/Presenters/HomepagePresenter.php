<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Form;
use Nette;


final class HomepagePresenter extends Nette\Application\UI\Presenter
{
    private $database;

	public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
    }
    
public function renderDefault(): void
{	$usr_id = $this->database->table ('users')->get($this->getUser()->id);;
	$this->template->user_name = $usr_id->username;	
	$this->template->posts = $this->database->table('posts')
		->order('time_created DESC')
		->limit(5);
}


// Form for adding new post

protected function createComponentPostForm(): Form
{
	$form = new Form;
	$form->addText('title', 'Title:')
		->setRequired();
	$form->addTextArea('content', 'Content:')
		->setRequired();
    $form->addSubmit('send', 'Save');
	$form->onSuccess[] = [$this, 'postFormSucceeded'];

	return $form;
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
	$this->redirect('default');
}


// Edit existing post


public function actionEdit(int $postId): void
{
    if (!$this->getUser()->isLoggedIn()) {
		$this->redirect('Sign:in');
	}
	$post = $this->database->table('posts')->get($postId);
	if (!$post) {
		$this->error('Post not found');
	}
	$this['postForm']->setDefaults($post->toArray());
}

// Delete existing post
public function actionDelete(int $postId): void
{
    $post = $this->database->table('posts')->get($postId);
	if (!$post) {
		$this->error('Post not found');
	}
	if ($postId) {
		$post = $this->database->table('posts')->get($postId);
		$post->delete($post);
	}

	$this->flashMessage('Post was deleted', 'success');
	$this->redirect('Homepage:default');
}

// Function checks if user is logged in

protected function startup()
{
	parent::startup();
	if (!$this->getUser()->isLoggedIn()) {
		$this->redirect('Sign:in');
	}
}

}
