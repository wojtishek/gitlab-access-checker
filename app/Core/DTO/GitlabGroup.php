<?php declare(strict_types = 1);

namespace App\Core\DTO;

class GitlabGroup
{

	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $path,
		public readonly string $fullPath,
		public readonly string $webUrl,
		public readonly string $description,
		public int $accessLevel = 0,
	)
	{
	}

	public static function create(
		int $id,
		string $name,
		string $path,
		string $fullPath,
		string $webUrl,
		string $description,
	): self
	{
		return new self($id, $name, $path, $fullPath, $webUrl, $description);
	}

}
