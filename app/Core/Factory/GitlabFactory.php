<?php declare(strict_types = 1);

namespace App\Core\Factory;

use App\Core\DTO\GitlabGroup;
use App\Core\DTO\GitlabMember;
use App\Core\DTO\GitlabProject;
use App\Core\Exception\GitlabException;
use App\Core\Service\GitlabService;
use function array_key_exists;
use function array_merge;

/**
 * Factory class responsible for creating and managing GitLab-related data structures.
 * Handles fetching and processing GitLab groups, members, and projects data through GitlabService.
 */
readonly class GitlabFactory
{

	public function __construct(private GitlabService $gitlabService)
	{
	}

    /**
     * Fetches data related to a GitLab group, including its members and project information.
     *
     * @param int $groupId The unique identifier of the GitLab group to fetch data for.
     * @return array An array containing information about the group's members and associated projects.
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


    /**
     * Fetches a top-level GitLab group and all its subgroups.
     *
     * @param int $groupId The ID of the top-level group to fetch
     * @return array Array containing the top-level group and all its subgroups
     * @throws GitlabException If there's an error fetching the group
     */
    private function fetchAllGroups(int $groupId): array
	{
		$gitlabTopGroup = $this->gitlabService->getGroup($groupId);
		$gitlabSubgroups = $this->gitlabService->getGroupDescendants($groupId);
		if (array_key_exists('error', $gitlabTopGroup)) {
			throw new GitlabException($gitlabTopGroup['error']);
		}

		return array_merge([$gitlabTopGroup['data']], $gitlabSubgroups['data']);
	}

    /**
     * Creates a GitlabGroup DTO from raw group data.
     *
     * @param array $gitlabGroup Raw group data from GitLab API
     * @return GitlabGroup Created DTO object
     */
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

    /**
     * Processes members of a GitLab group and adds them to the member collection.
     *
     * @param GitlabGroup $gitlabGroupDTO Group DTO to process members for
     * @param array $gitlabMembers Reference to the collection of members
     */
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

    /**
     * Processes projects belonging to a GitLab group and their members.
     *
     * @param array $gitlabGroup Raw group data containing projects information
     * @param GitlabGroup $gitlabGroupDTO Group DTO object
     * @param array $gitlabMembers Reference to the collection of members
     */
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

    /**
     * Creates a GitlabProject DTO from raw project data.
     *
     * @param array $project Raw project data from GitLab API
     * @return GitlabProject Created a DTO object
     */
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

    /**
     * Processes members of a GitLab project and adds them to the member collection.
     *
     * @param int $projectId ID of the project to process members for
     * @param GitlabProject $projectDTO Project DTO object
     * @param array $gitlabMembers Reference to the collection of members
     */
    private function processProjectMembers(int $projectId, GitlabProject $projectDTO, array &$gitlabMembers): void
	{
		$projectMembers = $this->gitlabService->getProjectMembers($projectId);

		foreach ($projectMembers['data'] as $projectMember) {
			$projectDTO->accessLevel = $projectMember['access_level'];
			$this->processAndAddMember($projectMember, $gitlabMembers);
			$gitlabMembers[$projectMember['id']]->projects[] = $projectDTO;
		}
	}

    /**
     * Creates and adds a GitlabMember DTO to the member collection if it doesn't exist.
     *
     * @param array $member Raw member data from GitLab API
     * @param array $gitlabMembers Reference to the collection of members
     */
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
