<?php

class MatchTest extends TestCase
{
    /** @var Team */
    protected $team_a;
    /** @var Team */
    protected $team_b;
    /** @var Match */
    protected $match;
    /** @var Match */
    protected $match_b;
    /** @var Player */
    protected $player_a;
    /** @var Player */
    protected $player_b;

    protected function setUp()
    {
        $this->connectToDatabase();

        $this->player_a = $this->getNewPlayer();
        $this->player_b = $this->getNewPlayer();

        $this->team_a = Team::createTeam("Team A", $this->player_a->getId(), "", "");
        $this->team_b = Team::createTeam("Team B", $this->player_b->getId(), "", "");
    }

    public function testTeamAWin()
    {
        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 5, 2, 30, $this->player_a->getId());

        $this->assertInstanceOf("Team", $this->match->getTeamA());
        $this->assertInstanceOf("Team", $this->match->getTeamB());

        $this->assertEquals(1225, $this->match->getTeamA()->getElo());
        $this->assertEquals(1175, $this->match->getTeamB()->getElo());

        $this->assertEquals(1225, $this->match->getTeamAEloNew());
        $this->assertEquals(1175, $this->match->getTeamBEloNew());

        $this->assertEquals(1200, $this->match->getTeamAEloOld());
        $this->assertEquals(1200, $this->match->getTeamBEloOLd());

        $this->assertEquals(5, $this->match->getTeamAPoints());
        $this->assertEquals(2, $this->match->getTeamBPoints());

        $this->assertEquals(30, $this->match->getDuration());

        $this->assertEquals(25, $this->match->getEloDiff());

        $this->assertFalse($this->match->isDraw());

        $this->assertEquals($this->team_a->getId(), $this->match->getWinner()->getId());
        $this->assertEquals($this->team_b->getId(), $this->match->getLoser()->getId());
    }

    public function testTeamBWin()
    {
        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 2, 5, 30, $this->player_a->getId());

        $this->assertEquals(1175, $this->match->getTeamAEloNew());
        $this->assertEquals(1225, $this->match->getTeamBEloNew());

        $this->assertEquals(1200, $this->match->getTeamAEloOld());
        $this->assertEquals(1200, $this->match->getTeamBEloOLd());

        $this->assertEquals(2, $this->match->getTeamAPoints());
        $this->assertEquals(5, $this->match->getTeamBPoints());

        $this->assertEquals(25, $this->match->getEloDiff());

        $this->assertFalse($this->match->isDraw());

        $this->assertEquals($this->team_b->getId(), $this->match->getWinner()->getId());
        $this->assertEquals($this->team_a->getId(), $this->match->getLoser()->getId());
    }

    public function testDraw()
    {
        $this->team_a->adjustElo(+10);

        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 3, 3, 30, $this->player_a->getId());

        $this->assertTrue($this->match->isDraw());

        $this->assertEquals(1, $this->match->getEloDiff());

        $this->assertEquals(1209, $this->match->getTeamAEloNew());
        $this->assertEquals(1201, $this->match->getTeamBEloNew());

        $this->assertEquals(1210, $this->match->getTeamAEloOld());
        $this->assertEquals(1200, $this->match->getTeamBEloOLd());

        $this->assertInstanceOf("Team", $this->match->getWinner());
        $this->assertInstanceOf("Team", $this->match->getLoser());
    }

    public function testDrawReverse()
    {
        $this->team_b->adjustElo(+10);

        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 3, 3, 30, $this->player_a->getId());

        $this->assertTrue($this->match->isDraw());

        $this->assertEquals(1, $this->match->getEloDiff());

        $this->assertEquals(1201, $this->match->getTeamAEloNew());
        $this->assertEquals(1209, $this->match->getTeamBEloNew());

        $this->assertEquals(1200, $this->match->getTeamAEloOld());
        $this->assertEquals(1210, $this->match->getTeamBEloOLd());

        $this->assertInstanceOf("Team", $this->match->getWinner());
        $this->assertInstanceOf("Team", $this->match->getLoser());
    }

    public function testEqualEloDraw()
    {
        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 3, 3, 30, $this->player_a->getId());

        $this->assertEquals(0, $this->match->getEloDiff());
        $this->assertEquals($this->match->getTeamAEloNew(), $this->match->getTeamBEloNew());
        $this->assertEquals($this->match->getTeamAEloOld(), $this->match->getTeamBEloOLd());
    }

    public function testShortMatch()
    {
        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 5, 2, 20, $this->player_a->getId());

        $this->assertEquals(20, $this->match->getDuration());

        $this->assertEquals(16, $this->match->getEloDiff());

        $this->assertEquals(1216, $this->match->getTeamAEloNew());
        $this->assertEquals(1184, $this->match->getTeamBEloNew());
    }

    public function testMiscMethods()
    {
        $old_matches = Match::getMatches();

        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 5, 2, 30, $this->player_a->getId());
        $this->match_b = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 5, 2, 20, $this->player_b->getId());

        $this->assertEquals("now", $this->match->getTimestamp()->diffForHumans());

        $this->assertEquals($this->player_a->getId(), $this->match->getEnteredBy()->getId());

        $this->assertEquals(5, $this->match->getScore($this->team_a->getId()));
        $this->assertEquals(2, $this->match->getOpponentScore($this->team_a->getId()));

        $this->assertEquals($this->team_a->getId(), $this->match->getOpponent($this->team_b->getId())->getId());

        $matches = Match::getMatches();
        $this->assertArrayContainsModel($this->match, $matches);
        $this->assertArrayContainsModel($this->match_b, $matches);
        $this->assertEquals(2, count($matches) - count($old_matches));
    }

    public function testFunMatchHasOnlyColorTeams()
    {
        $player_d = $this->getNewPlayer();
        $this->team_b->addMember($player_d->getId());

        $this->match = Match::enterMatch(
            null,
            $this->team_b->getId(),
            4,
            1,
            30,
            null,
            'now',
            [],
            [$this->player_b->getId(), $player_d->getId()],
            null,
            null,
            null,
            Match::FUN
        );

        $this->assertTrue($this->match->isValid());
        $this->assertEquals(Match::FUN, $this->match->getMatchType());
        $this->assertInstanceOf(ColorTeam::class, $this->match->getTeamA());
        $this->assertInstanceOf(ColorTeam::class, $this->match->getTeamB());
    }

    public function testExceptionThrownMixedHasNoRoster_TeamVsMixed()
    {
        $this->expectException(InvalidArgumentException::class);

        $player_c = $this->getNewPlayer();
        $this->team_a->addMember($player_c->getId());

        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            null,
            4,
            1,
            30,
            null,
            'now',
            [$this->team_a->getId(), $player_c],
            []
        );
    }

    public function testExceptionThrownMixedHasNoRoster_MixedVsTeam()
    {
        $this->expectException(InvalidArgumentException::class);

        $player_d = $this->getNewPlayer();
        $this->team_b->addMember($player_d->getId());

        $this->match = Match::enterMatch(
            null,
            $this->team_b->getId(),
            4,
            1,
            30,
            null,
            'now',
            [],
            [$this->player_b->getId(), $player_d->getId()]
        );
    }

    public function testIndividualPlayerEloDoesNotChangeInFunMatch()
    {
        $player_c = $this->getNewPlayer();
        $player_d = $this->getNewPlayer();

        $player_c->adjustElo(300, null);
        $player_d->adjustElo(500, null);

        $this->match = Match::enterMatch(
            null,
            null,
            5,
            1,
            30,
            null,
            'now',
            [$this->player_a->getId(), $this->player_b->getId()],
            [$player_c->getId(), $player_d->getId()],
            null,
            null,
            null,
            Match::FUN
        );

        $this->assertEquals(1200, $this->player_a->getElo());
        $this->assertEquals(1200, $this->player_b->getElo());
        $this->assertEquals(1500, $player_c->getElo());
        $this->assertEquals(1700, $player_d->getElo());
    }

    public function testIndividualPlayerEloChangesInOfficialMatch_TeamAWin()
    {
        $player_c = $this->getNewPlayer();
        $player_d = $this->getNewPlayer();

        $this->team_a->addMember($player_c->getId());
        $this->team_b->addMember($player_d->getId());

        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            $this->team_b->getId(),
            5,
            1,
            30,
            null,
            'now',
            [$this->player_a->getId(), $player_c->getId()],
            [$this->player_b->getId(), $player_d->getId()]
        );

        $this->assertEquals(1225, $this->team_a->getElo());
        $this->assertEquals(1175, $this->team_b->getElo());

        $this->assertEquals(1225, $this->player_a->getElo());
        $this->assertEquals(1225, $player_c->getElo());
        $this->assertEquals(1175, $this->player_b->getElo());
        $this->assertEquals(1175, $player_d->getElo());
    }

    public function testIndividualPlayerEloChangesInOfficialMatch_TeamBWin()
    {
        $player_c = $this->getNewPlayer();
        $player_d = $this->getNewPlayer();

        $this->team_a->addMember($player_c->getId());
        $this->team_b->addMember($player_d->getId());

        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            $this->team_b->getId(),
            1,
            5,
            30,
            null,
            'now',
            [$this->player_a->getId(), $player_c->getId()],
            [$this->player_b->getId(), $player_d->getId()]
        );

        $this->assertEquals(1225, $this->team_b->getElo());
        $this->assertEquals(1175, $this->team_a->getElo());

        $this->assertEquals(1225, $this->player_b->getElo());
        $this->assertEquals(1225, $player_d->getElo());
        $this->assertEquals(1175, $this->player_a->getElo());
        $this->assertEquals(1175, $player_c->getElo());
    }

    /**
     * When a match occurs with mixed team vs mixed team, the ELOs for the teams they belong too should not change but
     * their individual player ELOs should change.
     *
     * - Given Player A (Team A) and Player C (Team B) are the winners in this match, their individual ELOs should
     *   increase
     * - Given Player B (Team B) and Player D (Team A) are the losers in this match, their individual ELOs should
     *   decrease
     * - The ELOs for 'Team A' and 'Team B' should remain unchanged
     */
    public function testEloUpdatesMixedVsMixedOfficialMatch()
    {
        $player_c = $this->getNewPlayer();
        $player_d = $this->getNewPlayer();

        $this->team_a->addMember($player_d->getId());
        $this->team_b->addMember($player_c->getId());

        $this->match = Match::enterMatch(
            null,
            null,
            4,
            3,
            30,
            null,
            'now',
            [$this->player_a->getId(), $player_c->getId()],
            [$this->player_b->getId(), $player_d->getId()],
            null,
            null,
            null,
            Match::OFFICIAL,
            'red',
            'purple'
        );

        $this->assertGreaterThan(1200, $this->player_a->getElo());
        $this->assertGreaterThan(1200, $player_c->getElo());

        $this->assertLessThan(1200, $this->player_b->getElo());
        $this->assertLessThan(1200, $player_d->getElo());

        $this->assertEquals(1200, $this->team_a->getElo());
        $this->assertEquals(1200, $this->team_b->getElo());

        $this->assertEquals('red', $this->match->getTeamA()->getId());
        $this->assertEquals('purple', $this->match->getTeamB()->getId());
    }

    /**
     * When a match occurs with a team vs a mixed team, the ELOs for the complete team should change and the individual
     * ELOs for the participants should change.
     *
     * - Given Player A and Player C both belong to 'Team A' and lose a match, their individual ELOs should decrease and
     *   the ELO of their Team should decrease.
     * - Given Player B (Team B) and Player D (Teamless) win the match, their individual ELOs should increase but the ELO
     *   for Team B should remain unchanged
     */
    public function testEloUpdatesTeamVsMixedOfficialMatch()
    {
        $player_c = $this->getNewPlayer();
        $player_d = $this->getNewPlayer();

        $this->team_a->addMember($player_c->getId());

        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            null,
            3,
            4,
            30,
            null,
            'now',
            [$this->player_a->getId(), $player_c->getId()],
            [$this->player_b->getId(), $player_d->getId()],
            null,
            null,
            null,
            Match::OFFICIAL,
            'red',
            'purple'
        );

        $this->assertTrue($player_d->isTeamless());

        $this->assertEquals(1175, $this->team_a->getElo());
        $this->assertEquals(1200, $this->team_b->getElo());

        $this->assertGreaterThan(1200, $this->player_b->getElo());
        $this->assertGreaterThan(1200, $player_d->getElo());

        $this->assertLessThan(1200, $this->player_a->getElo());
        $this->assertLessThan(1200, $player_c->getElo());

        $this->assertInstanceOf(Team::class, $this->match->getTeamA());
        $this->assertInstanceOf(ColorTeam::class, $this->match->getTeamB());
    }

    public function testGetTeamMatchTypeIsTeamVsTeam()
    {
        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            $this->team_b->getId(),
            0,
            0,
            20,
            $this->player_a->getId()
        );

        $this->assertEquals(Match::TEAM_V_TEAM, $this->match->getTeamMatchType());
    }

    public function testGetTeamMatchTypeIsTeamVsMixed()
    {
        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            null,
            0,
            0,
            30,
            $this->player_a->getId(),
            'now',
            [],
            [$this->player_b->getId()]
        );

        $this->assertEquals(Match::TEAM_V_MIXED, $this->match->getTeamMatchType());
    }

    public function testGetTeamMatchTypeIsMixedVsMixed()
    {
        $this->match = Match::enterMatch(
            null,
            null,
            0,
            0,
            30,
            $this->player_a->getId(),
            'now',
            [$this->player_a->getId()],
            [$this->player_b->getId()]
        );

        $this->assertEquals(Match::MIXED_V_MIXED, $this->match->getTeamMatchType());
    }

    public function testEnterMatchWithColorTeamAndNoPlayerRoster()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            null,
            0,
            0,
            30,
            $this->player_a->getId(),
            'now',
            [],
            []
        );
    }

    public function testTeamEloRecalculationForOfficialMatch()
    {
        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            $this->team_b->getId(),
            1,
            0,
            30,
            null
        );

        $this->assertEquals(25, $this->match->getEloDiff());

        $this->team_a->adjustElo(300);
        $this->team_b->adjustElo(-200);

        $this->match->recalculateElo();

        $newElo = Match::calculateEloDiff(1500, 1000, 1, 0, 30);
        $this->assertEquals($newElo, $this->match->getEloDiff());
    }

    public function testPlayerEloRecalculationForOfficialMatch()
    {
        $player_c = $this->getNewPlayer();
        $player_d = $this->getNewPlayer();

        $this->team_a->addMember($player_c->getId());
        $this->team_b->addMember($player_d->getId());

        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            $this->team_b->getId(),
            2,
            1,
            30,
            null,
            'now',
            [$this->player_a->getId(), $player_c->getId()],
            [$this->player_b->getId(), $player_d->getId()]
        );

        $this->assertEquals(25, $this->match->getPlayerEloDiff(false));

        $this->match->setTeamPoints(1, 2);
        $this->match->recalculateElo();

        $this->assertEquals(-25, $this->match->getPlayerEloDiff(false));
    }

    public function testRecalculationForOfficialMatches()
    {
        $this->team_a->adjustElo(300);
        $this->team_b->adjustElo(100);

        $player_c = $this->getNewPlayer();
        $player_d = $this->getNewPlayer();

        $this->team_a->addMember($player_c->getId());
        $this->team_b->addMember($player_d->getId());

        $a_match_elo = Match::calculateEloDiff($this->team_a->getElo(), $this->team_b->getElo(), 5, 4, 30);
        $this->match = Match::enterMatch(
            $this->team_a->getId(),
            $this->team_b->getId(),
            5,
            4,
            30,
            null,
            '-1 day',
            [$this->player_a->getId(), $player_c->getId()],
            [$this->player_b->getId(), $player_d->getId()]
        );
        $this->assertEquals($a_match_elo, $this->match->getEloDiff(false));

        $b_match_elo = Match::calculateEloDiff($this->team_a->getElo(), $this->team_b->getElo(), 3, 1, 30);
        $this->match_b = Match::enterMatch(
            $this->team_a->getId(),
            $this->team_b->getId(),
            3,
            1,
            30,
            null,
            'now',
            [$this->player_a->getId(), $player_c->getId()],
            [$this->player_b->getId(), $player_d->getId()]
        );
        $this->assertEquals($b_match_elo, $this->match_b->getEloDiff(false));
        $this->assertNotEquals($this->match->getEloDiff(), $this->match_b->getEloDiff());

        $this->assertEquals(1500 + $a_match_elo + $b_match_elo, $this->team_a->getElo());
        $this->assertEquals(1300 - $a_match_elo - $b_match_elo, $this->team_b->getElo());
        $this->assertEquals(1200 + $this->match->getPlayerEloDiff(false) + $this->match_b->getPlayerEloDiff(false), $this->player_a->getElo());
        $this->assertEquals(1200 - $this->match->getPlayerEloDiff(false) - $this->match_b->getPlayerEloDiff(false), $this->player_b->getElo());

        $this->match->delete();

        ob_start();
        Match::recalculateMatchesSince($this->match);
        ob_end_clean();

        // Recreate the Match B object so the cached information is up to date
        $this->match_b = Match::get($this->match_b->getId());
        $playerEloDiff = $this->match_b->getPlayerEloDiff(false);

        $this->assertEquals($this->match->getPlayerEloDiff(false), $playerEloDiff);
        $this->assertEquals(1500 + $a_match_elo, $this->team_a->getElo());
        $this->assertEquals(1300 - $a_match_elo, $this->team_b->getElo());

        $this->assertEquals(1200 + $playerEloDiff, $this->player_a->getElo());
        $this->assertEquals(1200 - $playerEloDiff, $this->player_b->getElo());
    }

    public function testPlayerMatchParticipationIsRecorded()
    {
        $player_c = $this->getNewPlayer();
        $player_d = $this->getNewPlayer();

        $player_a_ip = '127.0.0.1';
        $player_b_ip = '127.0.0.2';
        $player_c_ip = '127.0.0.3';
        $player_d_ip = '127.0.0.4';

        $this->match = Match::enterMatch(
            null,
            null,
            0,
            0,
            30,
            null,
            'now',
            [$this->player_a->getId(), $player_c->getId()],
            [$this->player_b->getId(), $player_d->getId()],
            null,
            null,
            null,
            'official',
            'red',
            'purple',
            [$player_a_ip, $player_c_ip],
            [$player_b_ip, $player_d_ip],
            [$this->player_a->getName(), $player_c->getName()],
            [$this->player_b->getName(), $player_d->getName()]
        );

        $this->assertNotEmpty($this->match->getTeamAPlayers());
        $this->assertNotEmpty($this->match->getTeamBPlayers());
        $this->assertInstanceOf(Player::class, $this->match->getTeamAPlayers()[0]);
        $this->assertInstanceOf(Player::class, $this->match->getTeamBPlayers()[0]);
        $this->assertArrayContainsModel($this->player_a, $this->match->getTeamAPlayers());
        $this->assertArrayContainsModel($this->player_b, $this->match->getTeamBPlayers());

        $this->assertEquals($player_a_ip, $this->match->getPlayerIpAddress($this->player_a));
        $this->assertEquals($player_b_ip, $this->match->getPlayerIpAddress($this->player_b));
        $this->assertEquals($player_c_ip, $this->match->getPlayerIpAddress($player_c));
        $this->assertEquals($player_d_ip, $this->match->getPlayerIpAddress($player_d));

        $this->assertEquals($this->player_a->getName(), $this->match->getPlayerCallsign($this->player_a));
        $this->assertEquals($this->player_b->getName(), $this->match->getPlayerCallsign($this->player_b));
        $this->assertEquals($player_c->getName(), $this->match->getPlayerCallsign($player_c));
        $this->assertEquals($player_d->getName(), $this->match->getPlayerCallsign($player_d));
    }

    public function tearDown()
    {
        $this->wipe($this->match, $this->match_b, $this->team_a, $this->team_b);
        parent::tearDown();
    }
}
