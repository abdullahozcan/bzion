<?php

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class BanController extends CRUDController
{
    public function showAction(Ban $ban)
    {
        return array("ban" => $ban);
    }

    public function listAction(Request $request)
    {
        $currentPage = $this->getCurrentPage();

        $qb = $this->getQueryBuilder()
            ->orderBy('updated', 'DESC')
            ->limit(16)
            ->fromPage($currentPage)
        ;

        return array(
            'bans'        => $qb->getModels(true),
            'currentPage' => $currentPage,
            'totalPages'  => $qb->countPages()
        );
    }

    public function createAction(Player $me, Player $player = null)
    {
        $this->data->set('player', $player);

        return $this->create($me);
    }

    public function editAction(Player $me, Ban $ban)
    {
        return $this->edit($ban, $me, "ban");
    }

    public function deleteAction(Player $me, Ban $ban)
    {
        return $this->delete($ban, $me);
    }

    public function unbanAction(Player $me, Ban $ban)
    {
        if (!$this->canEdit($me, $ban)) {
            throw new ForbiddenException("You are not allowed to unban a player.");
        } elseif ($ban->isExpired()) {
            throw new ForbiddenException("Sorry, this ban has already expired.");
        }

        $victim = $ban->getVictim();

        return $this->showConfirmationForm(function () use ($ban) {
            $ban->expire();

            return new RedirectResponse($ban->getUrl());
        }, "Are you sure you want to unban <strong>{$victim->getEscapedUsername()}</strong>?",
            "{$victim->getUsername()}'s ban has been deactivated successfully", "Unban");
    }
}
