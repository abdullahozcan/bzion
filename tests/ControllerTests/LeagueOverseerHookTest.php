<?php

use Symfony\Bundle\FrameworkBundle\Client;

// Notes:
//  - URLs are hard coded in this class to ensure that if they change, there are appropriate redirects in place
//  - These POST requests are following the spec defined here: https://github.com/allejo/LeagueOverseer#match-reports

class LeagueOverseerHookTest extends TestCase
{
    /** @var Player */
    private $player_a, $player_b, $player_c, $player_d;
    /** @var Team */
    private $team_a, $team_b;
    /** @var Client */
    private $client;

    public function setUp()
    {
        $this->connectToDatabase();
        $this->client = self::createClient();

        $this->player_a = $this->getNewPlayer();
        $this->player_b = $this->getNewPlayer();
        $this->player_c = $this->getNewPlayer();
        $this->player_d = $this->getNewPlayer();

        $this->team_a = Team::createTeam('Team A', $this->player_a->getId(), '', '');
        $this->team_b = Team::createTeam('Team B', $this->player_b->getId(), '', '');
    }

    public function testTeamVsTeamReport()
    {
        $this->team_a->addMember($this->player_c->getId());
        $this->team_b->addMember($this->player_d->getId());

        $this->client->request('POST', '/api/leagueOverseer', [
            'query' => 'reportMatch',
            'apiVersion' => 1,
            'matchType' => 'official',
            'teamOneColor' => 'Red',
            'teamTwoColor' => 'Purple',
            'teamOneWins' => 5,
            'teamTwoWins' => 4,
            'duration' => 1800,
            'matchTime' => '17-08-01 13:00:00',
            'server' => 'localhost:5154',
            'port' => 5154,
            'replayFile' => 'my-replay-file.rec',
            'mapPlayed' => 'hix',
            'teamOnePlayers' => implode(',', [$this->player_a->getBZID(), $this->player_c->getBZID()]),
            'teamTwoPlayers' => implode(',', [$this->player_b->getBZID(), $this->player_d->getBZID()]),
            'teamOneIPs' => '127.0.0.1,127.0.0.2',
            'teamTwoIPs' => '127.0.0.3,127.0.0.4',
        ]);

        $this->assertEquals("(+/- 25) Team A [5] vs [4] Team B\n  player elo: +/- 25", $this->client->getResponse()->getContent());
    }

    // Despite the players being Team A vs Team B, we reported it as an FM and therefore should be treated as such
    public function testTeamVsTeamFmReport()
    {
        $this->team_a->addMember($this->player_c->getId());
        $this->team_b->addMember($this->player_d->getId());

        $this->client->request('POST', '/api/leagueOverseer', [
            'query' => 'reportMatch',
            'apiVersion' => 1,
            'matchType' => 'fm',
            'teamOneColor' => 'Red',
            'teamTwoColor' => 'Purple',
            'teamOneWins' => 5,
            'teamTwoWins' => 4,
            'duration' => 1800,
            'matchTime' => '17-08-01 13:00:00',
            'server' => 'localhost:5154',
            'port' => 5154,
            'replayFile' => 'my-replay-file.rec',
            'mapPlayed' => 'hix',
            'teamOnePlayers' => implode(',', [$this->player_a->getBZID(), $this->player_c->getBZID()]),
            'teamTwoPlayers' => implode(',', [$this->player_b->getBZID(), $this->player_d->getBZID()]),
            'teamOneIPs' => '127.0.0.1,127.0.0.2',
            'teamTwoIPs' => '127.0.0.3,127.0.0.4',
        ]);

        $this->assertEquals("Fun Match: Red Team [5] vs [4] Purple Team", $this->client->getResponse()->getContent());
    }

    public function testTeamVsMixedReport()
    {
        $this->team_a->addMember($this->player_c->getId());

        $this->client->request('POST', '/api/leagueOverseer', [
            'query' => 'reportMatch',
            'apiVersion' => 1,
            'matchType' => 'official',
            'teamOneColor' => 'Red',
            'teamTwoColor' => 'Purple',
            'teamOneWins' => 3,
            'teamTwoWins' => 5,
            'duration' => 1800,
            'matchTime' => '17-08-02 14:00:00',
            'server' => 'localhost:5154',
            'port' => 5154,
            'replayFile' => 'my-replay-file.rec',
            'mapPlayed' => 'hix',
            'teamOnePlayers' => implode(',', [$this->player_a->getBZID(), $this->player_c->getBZID()]),
            'teamTwoPlayers' => implode(',', [$this->player_b->getBZID(), $this->player_d->getBZID()]), // player D is teamless
            'teamOneIPs' => '127.0.0.1,127.0.0.2',
            'teamTwoIPs' => '127.0.0.3,127.0.0.4',
        ]);

        $this->assertEquals("(+/- 25) Purple Team [5] vs [3] Team A\n  player elo: +/- 25", $this->client->getResponse()->getContent());

        $this->assertEquals(1175, $this->team_a->getElo());
        $this->assertEquals(1200, $this->team_b->getElo());
        $this->assertEquals(1175, $this->player_a->getElo());
        $this->assertEquals(1225, $this->player_d->getElo());
    }

    // The same team playing against each other in an official match is disallowed; e.g. Team A vs Team A
    public function testSameTeamVsReport()
    {
        $player_e = $this->getNewPlayer();

        $this->team_a->addMember($this->player_c->getId());
        $this->team_a->addMember($this->player_d->getId());
        $this->team_a->addMember($player_e->getId());

        $this->client->request('POST', '/api/leagueOverseer', [
            'query' => 'reportMatch',
            'apiVersion' => 1,
            'matchType' => 'official',
            'teamOneColor' => 'Red',
            'teamTwoColor' => 'Purple',
            'teamOneWins' => 3,
            'teamTwoWins' => 5,
            'duration' => 1800,
            'matchTime' => '17-08-03 14:00:00',
            'server' => 'localhost:5154',
            'port' => 5154,
            'replayFile' => 'my-replay-file.rec',
            'mapPlayed' => 'hix',
            'teamOnePlayers' => implode(',', [$this->player_a->getBZID(), $this->player_c->getBZID()]),
            'teamTwoPlayers' => implode(',', [$this->player_d->getBZID(), $player_e->getBZID()]), // player D is teamless
            'teamOneIPs' => '127.0.0.1,127.0.0.2',
            'teamTwoIPs' => '127.0.0.3,127.0.0.4',
        ]);

        $this->assertContains('Holy sanity check, Batman!', $this->client->getResponse()->getContent());
    }

    /**
     * Helper function to wipe Matches that were created by the controller
     */
    private function wipeMatches()
    {
        /** @var Match[] $matches */
        $matches = Match::getQueryBuilder()
            ->getModels(true);

        foreach ($matches as $match) {
            $match->wipe();
        }
    }

    public function tearDown()
    {
        $this->wipeMatches();
        $this->wipe($this->team_a, $this->team_b);

        parent::tearDown();
    }
}
