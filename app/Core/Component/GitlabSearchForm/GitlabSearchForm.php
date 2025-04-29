<?php declare(strict_types = 1);

namespace App\Core\Component\GitlabSearchForm;

use App\Core\Exception\GitlabException;
use App\Core\Factory\GitlabFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class GitlabSearchForm extends Control
{

	public array $onDone = [];

	public array $onError = [];

	public function __construct(private readonly GitlabFactory $gitlabFactory)
	{
	}

	public function createComponentForm(): Form
	{
		$form = new Form();
		$form->getElementPrototype()->class('ajax');
		$form->addText('gitlabGroupId', 'Gitlab Group ID')
			->setRequired('Please enter a Gitlab Group ID.')
			->getControlPrototype()->class('form-control');
		$form->addSubmit('submit', 'Search')
			->getControlPrototype()->class('btn btn-primary');
		$form->onSuccess[] = [$this, 'formSucceeded'];

		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values): void
	{
		try {
			$gitlabMembers = $this->gitlabFactory->fetchGitlabGroupData((int) $values->gitlabGroupId);
			$this->onDone($gitlabMembers);
		} catch (GitlabException $e) {
			$this->onError($e->getMessage());
		}
	}

	public function render(): void
	{
		$this->getTemplate()->setFile(__DIR__ . '/GitlabSearchForm.latte')->render();
	}

}
