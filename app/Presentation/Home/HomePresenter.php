<?php declare(strict_types = 1);

namespace App\Presentation\Home;

use App\Core\Component\GitlabSearchForm\GitlabSearchForm;
use App\Core\Component\GitlabSearchForm\GitlabSearchFormFactory;
use App\Core\DTO\GitlabMember;
use Nette;

final class HomePresenter extends Nette\Application\UI\Presenter
{

	public function __construct(
		private readonly GitlabSearchFormFactory $gitlabSearchFormFactory,
	)
	{
		parent::__construct();
	}

	public function renderDefault(): void
	{
		$this->getTemplate()->accessLevels = GitlabMember::ACCESS_LEVELS;
	}

	protected function createComponentGitlabSearchForm(): GitlabSearchForm
	{
		$form = $this->gitlabSearchFormFactory->create();
		$form->onDone[] = function ($gitlabTopGroup): void {
			$this->getTemplate()->searchResult = $gitlabTopGroup;
			$this->redrawControl('searchResult');
		};
		$form->onError[] = function ($message): void {
			$this->flashMessage($message, 'error');
		};

		return $form;
	}

}
