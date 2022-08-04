<?php 

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;

class PaginationService {

	public function __construct(private UrlGeneratorInterface $urlGenerator) {

	}

	/**
	 * Used to generate paginator
	 * @param Request request
	 * @param QueryBuilder query
	 * @param int currentPage = 1
	 * @param int limit = 10
	 * @return array of ['data', 'currentPage', 'nextUrl', 'prevUrl', 'availablePages', 'availableElements']
	 */
	public function getPaginator(Request $request, QueryBuilder $queryBuilder, int $currentPage = 1, int $limit = 10) {
		$availableElements = count($queryBuilder->getQuery()->getResult());
		$availablePages = (int)($availableElements / $limit);
		$availablePages += (($availableElements % $limit) != 0) ? 1 : 0;
		$offset = ($currentPage - 1) * $limit;
		$prevUrl = ($currentPage > 1) ? $this->regeneratePaginationUrl($request, $currentPage, false) : null;
		$nextUrl = ($currentPage < $availablePages) ? $this->regeneratePaginationUrl($request, $currentPage, true) : null;
		// ajouter les conditions de paginations
		$queryBuilder->setFirstResult($offset);
		$queryBuilder->setMaxResults($limit);

		$data = $queryBuilder->getQuery()->getResult();

		return compact('data', 'currentPage', 'nextUrl', 'prevUrl', 'availablePages', 'availableElements');
	}

	/**
	 * Used to regenarating url by not touching parameters except pagination
	 * @param Request $request
	 * @param int $pagination [1 by default]
	 * @param bool $isNext [true by default]  
	 * @return url
	 */
	private function regeneratePaginationUrl(Request $request, int $page = 1, bool $isNext = true) {
		$routeName = $request->attributes->get('_route');
		$routeParams = $request->attributes->get('_route_params');
		$url = $this->urlGenerator->generate($routeName, $routeParams);
		/*refactoring querystring*/
		$queryStringParams = $request->query->all();
		$initied = false;
		foreach($queryStringParams as $key => $value) {
			if(!$initied) {
				$initied = true;
				$url .= '?';
			}
			else
				$url .= '&';
			$url .= "$key=$value";
		}

		return $url;
	}
}