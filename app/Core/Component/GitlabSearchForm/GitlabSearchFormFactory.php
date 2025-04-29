<?php declare(strict_types = 1);

namespace App\Core\Component\GitlabSearchForm;

interface GitlabSearchFormFactory
{

	public function create(): GitlabSearchForm;

}
