<?php

namespace App\Actions;

use App\Enums\ComparisonExpressionTypeEnum;
use App\Interfaces\ActionInterface;
use App\Models\Fragment;
use App\Models\Gene;
use App\Models\Metadata;
use App\Traits\DefaultCacheFileTrait;
use App\Traits\Makeable;
use App\TrfExplorer\CommandExecutor;

/**
 * @implements ActionInterface<array>
 */
class ComputeMostMediatedCorrelatedTableAction implements ActionInterface
{

    use DefaultCacheFileTrait;
    use Makeable;

    private readonly ?string $fragment;
    private readonly ?string $gene;
    private readonly string $type;
    private readonly string $metadata;
    /**
     * @var array<string>
     */
    private readonly array $covariates;

    public function __construct(
        private readonly string $dataset,
        Fragment|string|null $fragment,
        Gene|string|null $gene,
        Metadata|string $metadata,
        private readonly array $subtypes = [],
        private readonly array $sampleTypes = [],
        ComparisonExpressionTypeEnum $type = ComparisonExpressionTypeEnum::GENES,
        array $covariates = []
    ) {
        $this->metadata = $metadata instanceof Metadata ? $metadata->name : $metadata;
        if (!is_null($fragment)) {
            $this->fragment = $fragment instanceof Fragment ? $fragment->name : $fragment;
        } else {
            $this->fragment = null;
        }
        if (!is_null($gene)) {
            if (!($gene instanceof Gene)) {
                $gene = Gene::where('gene_id', $gene)->orWhere('gene_name', $gene)->firstOrFail();
            }
            $this->gene = $gene->gene_name;
            $this->type = $gene->dataset_type->value;
        } else {
            $this->gene = null;
            $this->type = $type->value;
        }
        $this->covariates = array_map(
            static fn(string|Metadata $covariate) => $covariate instanceof Metadata ? $covariate->name : $covariate,
            $covariates
        );
    }

    /**
     * @throws \JsonException
     * @throws \App\Exceptions\ProcessingJobException
     */
    public function handle(): array
    {
        $cacheFile = $this->getCacheFile();
        if (!file_exists($cacheFile)) {
            CommandExecutor::forScript('compute_most_mediated_correlated')
                           ->withArguments(
                               '-d',
                               $this->dataset,
                               '-m',
                               $this->metadata,
                               '-e',
                               config('trfuniverse.expressions_path'),
                               '-t',
                               $this->type,
                               '-o',
                               $cacheFile,
                           )
                           ->withConditionalArguments(!empty($this->subtypes), '-s', $this->subtypes)
                           ->withConditionalArguments(!empty($this->sampleTypes), '-a', $this->sampleTypes)
                           ->withConditionalArguments(!empty($this->covariates), '-v', $this->covariates)
                           ->withConditionalArguments(!empty($this->fragment), '-f', $this->fragment)
                           ->withConditionalArguments(!empty($this->gene), '-g', $this->gene)
                           ->withCwd(dirname($cacheFile))
                           ->execute();
        }

        return json_decode(file_get_contents($cacheFile), true, 512, JSON_THROW_ON_ERROR);
    }
}