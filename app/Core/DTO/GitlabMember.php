<?php declare(strict_types = 1);

namespace App\Core\DTO;

class GitlabMember
{

	public const array ACCESS_LEVELS = [
		0 => 'No access',
		5 => 'Minimal access',
		10 => 'Guest',
		15 => 'Planner',
		20 => 'Reporter',
		30 => 'Developer',
		40 => 'Maintainer',
		50 => 'Owner',
	];

	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $username,
		public array $groups = [],
		public array $projects = [],
	)
	{
	}

	public static function create(int $id, string $name, string $username): self
	{
		return new self($id, $name, $username);
	}

}
