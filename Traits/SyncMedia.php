<?php

namespace App\Traits;

use App\Http\Resources\MediaResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Exceptions\MediaCannotBeUpdated;

trait SyncMedia
{
    public function syncMedia(array $newMediaArray, array $keepMediaArray = [], string $collectionName = 'default'): Collection
    {
        $newMediaArray = array_filter($newMediaArray);

        foreach ($newMediaArray as &$media) {
            if($media instanceof UploadedFile) {
                continue;
            } else {
                $media = json_decode($media, true);
            }
        }

        unset($media);

        if ($newMediaArray) {
            $this->removeMediaNotInArray(array_merge($newMediaArray, $keepMediaArray), $collectionName);
        } else {
            $this->removeMediaNotInArray([], $collectionName);
        }

        return collect($newMediaArray)
            ->map(function ($newMedium) use ($collectionName) {
                static $orderColumn = 1;

                if ($newMedium instanceof UploadedFile) {
                    $currentMedium = $this->addMedia($newMedium)->toMediaCollection($collectionName);
                    $currentMedium->update(['order_column' => $orderColumn++]);
                    return $currentMedium;
                }

                $mediaClass = config('medialibrary.media_model');
                $currentMedium = $mediaClass::findOrFail($newMedium['id']);

                if ($currentMedium->collection_name !== $collectionName) {
                    throw MediaCannotBeUpdated::doesNotBelongToCollection($collectionName, $currentMedium);
                }

                if (array_key_exists('name', $newMedium)) {
                    $currentMedium->name = $newMedium['name'];
                }

                if (array_key_exists('custom_properties', $newMedium)) {
                    $currentMedium->custom_properties = $newMedium['custom_properties'];
                }

                $currentMedium->order_column = $orderColumn++;
                $currentMedium->save();
                return $currentMedium;
            })
            ->mapInto(MediaResource::class);
    }

    protected function removeMediaNotInArray(array $array, string $collectionName = 'default')
    {
        $idsToKeep = collect($array)
            ->reject(function ($medium) {
                return $medium instanceof UploadedFile;
            })
            ->pluck('id')
            ->unique()->values()->toArray();

        $this->getMedia($collectionName)
            ->reject(function ($medium) use ($idsToKeep) {
                return in_array($medium->id, $idsToKeep);
            })
            ->each->delete();
    }
}
