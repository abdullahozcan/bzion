<?php

class MapController extends CRUDController
{
    public function listAction(Map $map = null)
    {
        if ($map === null) {
            $maps = $this->getQueryBuilder()
                ->active()
                ->orderBy('name')
                ->getModels()
            ;
        } else {
            $maps = [$map];
        }

        return [
            'maps' => $maps
        ];
    }

    public function createAction(Player $me)
    {
        return $this->create($me);
    }

    public function editAction(Player $me, Map $map)
    {
        return $this->edit($map, $me, "map");
    }

    public function deleteAction(Player $me, Map $map)
    {
        return $this->delete($map, $me);
    }

    protected function redirectTo($model)
    {
        // Redirect to the map list after creating/editing a map
        return $this->redirectToList($model);
    }
}
