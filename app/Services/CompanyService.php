<?php
declare(strict_types=1);
namespace App\Services;

use App\Data\CountryContext;
use App\Repositories\CompanyRepository;
use App\Support\{Cache,Pagination};

final class CompanyService {
    public function __construct(private CompanyRepository $repo,private Cache $cache) {}
    public function repo(): CompanyRepository { return $this->repo; }
    public function catalogue(CountryContext $context,array $filters,int $page=1,int $perPage=18): array {
        $pagination=new Pagination(max(1,$page),$perPage,0);$result=$this->repo->catalogue($context->id(),$filters,$pagination);
        $result['pagination']=new Pagination(max(1,$page),$perPage,(int)$result['total']);return$result;
    }
    public function company(CountryContext $context,string $identifier): ?array { return $this->repo->publicCompany($context->id(),$identifier); }
    public function featured(CountryContext $context,int $limit=6): array { return $this->repo->featured($context->id(),$limit); }
}
