<?php

namespace KirschbaumDevelopment\NovaInlineRelationship\Observers;

use Laravel\Nova\Exceptions\ResourceMissingException;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Illuminate\Database\Eloquent\Model;
use KirschbaumDevelopment\NovaInlineRelationship\Integrations\Integrate;
use KirschbaumDevelopment\NovaInlineRelationship\NovaInlineRelationship;
use KirschbaumDevelopment\NovaInlineRelationship\Contracts\RelationshipObservable;
use KirschbaumDevelopment\NovaInlineRelationship\Helpers\NovaInlineRelationshipHelper;

class NovaInlineRelationshipObserver
{
    /**
     * Handle updating event for the model
     *
     * @param  Model  $model
     *
     * @return void
     * @throws ResourceMissingException
     */
    public function updating(Model $model): void
    {
        $this->callEvent($model, 'updating');
    }

    /**
     * Handle updated event for the model
     *
     * @param  Model  $model
     *
     * @return void
     * @throws ResourceMissingException
     */
    public function created(Model $model): void
    {
        $this->callEvent($model, 'created');
    }

    /**
     * Handle updating event for the model
     *
     * @param  Model  $model
     *
     * @return void
     * @throws ResourceMissingException
     */
    public function creating(Model $model): void
    {
        $this->callEvent($model, 'creating');
    }

    /**
     * Handle events for the model
     *
     * @param  Model  $model
     * @param  string  $event
     *
     * @return void
     * @throws ResourceMissingException
     */
    public function callEvent(Model $model, string $event): void
    {
        $modelClass = get_class($model);

        $relationships = $this->getModelRelationships($model);

        $relatedModelAttribs = NovaInlineRelationship::$observedModels[$modelClass];

        foreach ($relationships as $relationship) {
            $observer = $this->getRelationshipObserver($model, $relationship);

            if ($observer instanceof RelationshipObservable) {
                $observer->{$event}($model, $relationship, $relatedModelAttribs[$relationship] ?? []);
            }
        }
    }

    /**
     * Checks if a relationship is singular
     *
     * @param  Model  $model
     * @param $relationship
     *
     * @return RelationshipObservable|null
     */
    public function getRelationshipObserver(Model $model, $relationship): ?RelationshipObservable
    {
        $className = NovaInlineRelationshipHelper::getObserver($model->{$relationship}());

        return class_exists($className) ? resolve($className) : null;
    }

    /**
     * @param  Model  $model
     *
     * @return array
     * @throws ResourceMissingException
     */
    protected function getModelRelationships(Model $model): array
    {
        return collect(Nova::newResourceFromModel($model)->fields(NovaRequest::createFrom(request())))
            ->flatMap(function ($value) {
                return Integrate::fields($value);
            })
            ->filter(function ($value) {
                return $value->component === 'nova-inline-relationship';
            })
            ->pluck('attribute')
            ->all();
    }
}
