<?php declare(strict_types = 1);

namespace App\Core\DTO;

class GitlabProject
{

	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $path,
		public readonly string $pathWithNamespace,
		public readonly string $webUrl,
		public readonly string $description,
		public readonly string $defaultBranch,
		public int $accessLevel = 0,
	)
	{
	}

	public static function create(
		int $id,
		string $name,
		string $path,
		string $pathWithNamespace,
		string $webUrl,
		string $description,
		string $defaultBranch,
	): self
	{
		return new self($id, $name, $path, $pathWithNamespace, $webUrl, $description, $defaultBranch);
	}

}
