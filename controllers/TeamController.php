<?php

class TeamController extends HTMLController {

    public function showAction(Team $team) {
        return array("team" => $team);
    }

    public function listAction() {
        return array("teams" => Team::getTeams());
    }
}
