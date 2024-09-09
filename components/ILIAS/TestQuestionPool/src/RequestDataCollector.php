<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace ILIAS\TestQuestionPool;

use Closure;
use GuzzleHttp\Psr7\UploadedFile;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\Refinery\ByTrying;
use ILIAS\Repository\BaseGUIRequest;
use ILIAS\Refinery\ConstraintViolationException;
use ILIAS\HTTP\Services;
use ILIAS\Refinery\Factory;
use ILIAS\FileUpload\FileUpload;

class RequestDataCollector
{
    use BaseGUIRequest;

    public function __construct(
        Services $http,
        Factory $refinery,
        protected readonly FileUpload $upload
    ) {
        $this->initRequest($http, $refinery);
    }

    /**
     * @return UploadResult[]
     * @throws IllegalStateException
     */
    public function getProcessedUploads(): array
    {
        $uploads = [];

        if ($this->upload->hasUploads()) {
            if (!$this->upload->hasBeenProcessed()) {
                $this->upload->process();
            }
            $uploads = $this->upload->getResults();
        }

        return $uploads;
    }

    /**
     * @param string[] $http_names An array of keys used as structure for the HTTP name (e.g. ['terms', 'image'] for $_FILES['terms']['image'])
     * @param int $index
     * @return string|null
     */
    public function getUploadFilename(array $http_names, int $index): ?string
    {
        $uploaded_files = $this->http->request()->getUploadedFiles();

        while (($current_key = array_shift($http_names)) !== null) {
            if (!isset($uploaded_files[$current_key])) {
                return null;
            }

            $uploaded_files = $uploaded_files[$current_key];

            if (isset($uploaded_files[$index]) && $http_names === []) {
                /** @var UploadedFile $file */
                $file = $uploaded_files[$index];
                $c = Closure::bind(static function (UploadedFile $file): ?string {
                    return $file->file ?? null;
                }, null, $file);

                return $c($file);
            }
        }

        return null;
    }

    public function upload(): FileUpload
    {
        return $this->upload;
    }

    public function isset(string $key): bool
    {
        return $this->raw($key) !== null;
    }

    public function hasRefId(): int
    {
        return $this->raw('ref_id') !== null;
    }

    public function getRefId(): int
    {
        return $this->int('ref_id');
    }

    public function hasQuestionId(): bool
    {
        return $this->raw('q_id') !== null;
    }

    public function getQuestionId(): int
    {
        return $this->int('q_id');
    }

    /**
     * @return string[]
     */
    public function getIds(): array
    {
        return $this->strArray('id');
    }

    /**
     * @return mixed|null
     */
    public function raw(string $key): mixed
    {
        return $this->get($key, $this->refinery->identity());
    }

    public function float(string $key): float
    {
        try {
            return $this->get($key, $this->refinery->kindlyTo()->float()) ?? 0.0;
        } catch (ConstraintViolationException $e) {
            return 0.0;
        }
    }

    public function string(string $key): string
    {
        return $this->get($key, $this->refinery->kindlyTo()->string()) ?? '';
    }

    public function getParsedBody(): object|array|null
    {
        return $this->http->request()->getParsedBody();
    }

    /**
     * @return array<int>
     */
    public function getUnitIds(): array
    {
        return $this->intArray('unit_ids');
    }

    /**
     * @return array<int>
     */
    public function getUnitCategoryIds(): array
    {
        return $this->intArray('category_ids');
    }

    public function getMatchingPairs(): array
    {
        if (!$this->http->wrapper()->post()->has('matching')) {
            return [];
        }

        return $this->http->wrapper()->post()->retrieve(
            'matching',
            $this->refinery->byTrying([
                $this->refinery->container()->mapValues(
                    $this->refinery->custom()->transformation(
                        fn(string $v): array => $this->refinery->container()->mapValues(
                            $this->refinery->kindlyTo()->int()
                        )->transform(json_decode($v))
                    )
                ),
                $this->refinery->always([])
            ])
        );
        return array_map(
            fn(string $v): array => json_decode($v),
            $this->questionpool_request->retrieveArrayOfStringsFromPost('matching')
        );
    }

    /**
     * @return ?array<int, string>
     */
    public function retrieveArrayOfStringsFromPost(string $key, ?array $fallback = null): ?array
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->container()->mapValues(
                    $this->refinery->in()->series(
                        [
                            $this->refinery->kindlyTo()->string(),
                            $this->refinery->custom()->transformation(
                                fn($v) => trim($v)
                            )
                        ]
                    )
                ),
                $this->refinery->always($fallback)
            ])
        );
    }

    /**
     * @return ?array<int, string>
     */
    public function retrieveArrayOfIntsFromPost(string $key, ?array $fallback = null): ?array
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->container()->mapValues(
                    $this->refinery->kindlyTo()->int()
                ),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveStringValueFromPost(string $key, ?string $fallback = null): ?string
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->in()->series(
                [
                    $this->refinery->kindlyTo()->string(),
                    $this->refinery->custom()->transformation(
                        fn($v) => trim($v)
                    ),
                    $this->refinery->always($fallback)
                ]
            )
        );
    }

    public function retrieveIntValueFromPost(string $key, ?int $fallback = null): ?int
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->int(),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveFloatValueFromPost(string $key, ?float $fallback = null): ?float
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->float(),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveFloatArrayOrIntArrayFromPost(string $key, ?array $fallback = null): ?array
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->float()),
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveNestedArraysOfStrings(string $key, int $depth = 1, mixed $fallback = null): ?array
    {
        $kindly_to_string = $this->refinery->kindlyTo()->string();
        $kindly_to_list_of = null;

        for ($i = 0; $i < $depth; $i++) {
            $kindly_to_list_of = $this->refinery->kindlyTo()->listOf($kindly_to_list_of ?? $kindly_to_string);
        }

        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $kindly_to_list_of,
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveNestedArraysOfInts(string $key, int $depth = 1, ?array $fallback = null): ?array
    {
        $kindly_to_int = $this->refinery->kindlyTo()->int();
        $kindly_to_list_of = null;

        for ($i = 0; $i < $depth; $i++) {
            $kindly_to_list_of = $this->refinery->kindlyTo()->listOf($kindly_to_list_of ?? $kindly_to_int);
        }

        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $kindly_to_list_of,
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveNestedArraysOfFloats(string $key, int $depth = 1, ?array $fallback = null): ?array
    {
        $kindly_to_float = $this->refinery->kindlyTo()->float();
        $kindly_to_list_of = null;

        for ($i = 0; $i < $depth; $i++) {
            $kindly_to_list_of = $this->refinery->kindlyTo()->listOf($kindly_to_list_of ?? $kindly_to_float);
        }

        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $kindly_to_list_of,
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveArrayOfStringWithFilter(string $key, callable $filter = null, ?array $fallback = null): ?array
    {
        $result = $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always($fallback)
            ])
        );
        return ($result === null) ? null : array_filter($result, $filter);
    }

    public function retrieveStringFromPost(string $key, ?string $fallback = null): ?string
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveBoolFromPost(string $key, ?bool $fallback = null): ?bool
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->bool(),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveArraysOfInts(string $key, ?array $fallback = null): ?array
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveArrayOfIdentities(string $key, ?array $fallback = null): ?array
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->identity()),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveMappedValuesOfStringFromPost(?array $fallback = null): ByTrying
    {
        return $this->refinery->byTrying(
            [
                $this->refinery->container()->mapValues(
                    $this->refinery->kindlyTo()->string()
                ),
                $this->refinery->always($fallback)
            ]
        );
    }

    public function retrieveMappedValuesOfIntFromPost(?array $fallback = null): ByTrying
    {
        return $this->refinery->byTrying(
            [
                $this->refinery->container()->mapValues(
                    $this->refinery->kindlyTo()->int()
                ),
                $this->refinery->always($fallback)
            ]
        );
    }
    public function retrieveMappedValuesOfFloatFromPost(?array $fallback = null): ByTrying
    {
        return $this->refinery->byTrying(
            [
                $this->refinery->container()->mapValues(
                    $this->refinery->kindlyTo()->float()
                ),
                $this->refinery->always($fallback)
            ]
        );
    }

    public function getQueryKeys(): array
    {
        return $this->http->wrapper()->query()->keys();
    }

    public function getPostKeys(): array
    {
        return $this->http->wrapper()->post()->keys();
    }

    public function retrieveArrayOfIntsOrStringsFromPost(string $key, array $fallback = []): array
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()),
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->string()),
                $this->refinery->always($fallback),
            ])
        );
    }

    public function retrieveArrayOfArraysOfStringsFromPost(string $key, ?array $fallback = []): ?array
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf(
                    $this->refinery->kindlyTo()->listOf(
                        $this->refinery->kindlyTo()->string()
                    )
                ),
                $this->refinery->always($fallback)
            ])
        );
    }

    public function retrieveArrayOfBoolsFromPost(string $key, ?array $fallback = []): ?array
    {
        return $this->http->wrapper()->post()->retrieve(
            $key,
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->bool()),
                $this->refinery->always($fallback)
            ])
        );
    }
}
