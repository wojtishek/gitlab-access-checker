<?php declare(strict_types = 1);

namespace App\Core\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Tracy\Debugger;
use function array_merge;
use function json_decode;
use function sprintf;

class GitlabService
{

	public Client $client;

	public function __construct(
		private readonly string $gitlabToken,
		private readonly string $gitlabUrl = 'https://gitlab.com/api/v4',
	)
	{
		$this->client = new Client();
	}

	public function getGroup(int|string $gitlabGroupId): array
	{
		return $this->request('GET', "groups/$gitlabGroupId");
	}

	public function getGroupDescendants(int|string $gitlabGroupId): array
	{
		return $this->request('GET', "groups/$gitlabGroupId/descendant_groups", ['query' => ['with_projects' => true]]);
	}

	public function getGroupMembers(int|string $gitlabGroupId): array
	{
		return $this->request('GET', "groups/$gitlabGroupId/members");
	}

	public function getGroupProjects(int|string $gitlabGroupId): array
	{
		return $this->request('GET', "groups/$gitlabGroupId/projects");
	}

	public function getProjectMembers(int|string $gitlabProjectId): array
	{
		return $this->request('GET', "projects/$gitlabProjectId/members");
	}

	private function request(string $method, string $endpoint, array $options = []): array
	{
		$options = array_merge($this->baseHeaders(), $options);
		$uri = $this->buildApiUrl($endpoint);

		try {
			$response = $this->client->request($method, $uri, $options);
			$result = $this->parseResponse($response);
			$pagination = $this->extractPaginationInfo($response);

			if ($pagination['total_pages'] > 1) {
				$allData = $result;

				for ($page = 2; $page <= $pagination['total_pages']; $page++) {
					$pageOptions = $options;

					if (!isset($pageOptions['query'])) {
						$pageOptions['query'] = [];
					}

					$pageOptions['query']['page'] = $page;

					$pageResponse = $this->client->request($method, $uri, $pageOptions);
					$pageData = $this->parseResponse($pageResponse);
					$allData = array_merge($allData, $pageData);
				}

				return [
					'data' => $allData,
					'pagination' => $pagination,
					'all_pages_fetched' => true,
				];
			}

			return [
				'data' => $result,
				'pagination' => $pagination,
				'all_pages_fetched' => ($pagination['total_pages'] <= 1),
			];
		} catch (GuzzleException $e) {
			return $this->handleRequestError($e);
		}
	}

	private function buildApiUrl(string $endpoint): string
	{
		return sprintf('%s/%s', $this->gitlabUrl, $endpoint);
	}

	private function parseResponse(ResponseInterface $response): array
	{
		return json_decode($response->getBody()->getContents(), true) ?? [];
	}

	private function handleRequestError(GuzzleException $e): array
	{
		$error = [
			'error' => $e->getMessage(),
			'type' => $e::class,
			'code' => $e->getCode(),
		];

		Debugger::log($error, Debugger::ERROR);

		return $error;
	}

	private function baseHeaders(): array
	{
		return [
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'PRIVATE-TOKEN' => $this->gitlabToken,
			],
		];
	}

	private function extractPaginationInfo(ResponseInterface $response): array
	{
		$headers = $response->getHeaders();

		return [
			'page' => $headers['x-page'][0] ?? null,
			'per_page' => $headers['x-per-page'][0] ?? null,
			'total' => $headers['x-total'][0] ?? null,
			'total_pages' => $headers['x-total-pages'][0] ?? null,
		];
	}

}
