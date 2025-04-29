<?php declare(strict_types = 1);

namespace App\Core\Factory;

use App\Core\DTO\GitlabGroup;
use App\Core\DTO\GitlabMember;
use App\Core\DTO\GitlabProject;
use App\Core\Exception\GitlabException;
use App\Core\Service\GitlabService;
use function array_key_exists;
use function array_merge;

readonly class GitlabFactory
{

	public function __construct(private GitlabService $gitlabService)
	{
	}

	/**
	 * @throws GitlabException
	 */
	public function fetchGitlabGroupData(int $groupId): array
	{
		$gitlabGroups = $this->fetchAllGroups($groupId);
		$gitlabMembers = [];

		foreach ($gitlabGroups as $gitlabGroup) {
			$gitlabGroupDTO = $this->createGitlabGroupDTO($gitlabGroup);
			$this->processGroupMembers($gitlabGroupDTO, $gitlabMembers);
			$this->processGroupProjects($gitlabGroup, $gitlabGroupDTO, $gitlabMembers);
		}

		return $gitlabMembers;
	}

	private function fetchAllGroups(int $groupId): array
	{
		$gitlabTopGroup = $this->gitlabService->getGroup($groupId);
		$gitlabSubgroups = $this->gitlabService->getGroupDescendants($groupId);
		if (array_key_exists('error', $gitlabTopGroup)) {
			throw new GitlabException($gitlabTopGroup['error']);
		}

		return array_merge([$gitlabTopGroup['data']], $gitlabSubgroups['data']);
	}

	private function createGitlabGroupDTO(array $gitlabGroup): GitlabGroup
	{
		return GitlabGroup::create(
			$gitlabGroup['id'],
			$gitlabGroup['name'],
			$gitlabGroup['path'],
			$gitlabGroup['full_path'],
			$gitlabGroup['web_url'],
			$gitlabGroup['description'],
		);
	}

	private function processGroupMembers(GitlabGroup $gitlabGroupDTO, array &$gitlabMembers): void
	{
		$groupMembers = $this->gitlabService->getGroupMembers($gitlabGroupDTO->id);

		foreach ($groupMembers['data'] as $groupMember) {
			$gitlabGroupDTO->accessLevel = $groupMember['access_level'];
			$this->processAndAddMember($groupMember, $gitlabMembers);
			$gitlabMembers[$groupMember['id']]->groups[$gitlabGroupDTO->id] = $gitlabGroupDTO;
			$gitlabMembers[$groupMember['id']]->projects = [];
		}
	}

	private function processGroupProjects(array $gitlabGroup, GitlabGroup $gitlabGroupDTO, array &$gitlabMembers): void
	{
		$groupProjects = $gitlabGroup['projects'] ?? $this->gitlabService->getGroupProjects(
			$gitlabGroupDTO->id,
		)['data'];

		foreach ($groupProjects as $groupProject) {
			$projectDTO = $this->createProjectDTO($groupProject);
			$this->processProjectMembers($groupProject['id'], $projectDTO, $gitlabMembers);
		}
	}

	private function createProjectDTO(array $project): GitlabProject
	{
		return GitlabProject::create(
			$project['id'],
			$project['name'],
			$project['path'],
			$project['path_with_namespace'],
			$project['web_url'],
			$project['description'],
			$project['default_branch'],
		);
	}

	private function processProjectMembers(int $projectId, GitlabProject $projectDTO, array &$gitlabMembers): void
	{
		$projectMembers = $this->gitlabService->getProjectMembers($projectId);

		foreach ($projectMembers['data'] as $projectMember) {
			$projectDTO->accessLevel = $projectMember['access_level'];
			$this->processAndAddMember($projectMember, $gitlabMembers);
			$gitlabMembers[$projectMember['id']]->projects[] = $projectDTO;
		}
	}

	private function processAndAddMember(array $member, array &$gitlabMembers): void
	{
		if (!isset($gitlabMembers[$member['id']])) {
			$gitlabMembers[$member['id']] = GitlabMember::create(
				$member['id'],
				$member['name'],
				$member['username'],
			);
		}
	}

}
