<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Services\GameService;

final class GameController
{
    public function __construct(private readonly array $config)
    {
    }

    public function state(): void
    {
        $userId = Auth::userId();
        if (!$userId) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        Response::json(['ok' => true, 'data' => (new GameService())->dashboard($userId)]);
    }

    public function work(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $resource = (string) ($_POST['resource'] ?? 'gold');
        $result = (new GameService())->work($userId, $resource);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function battle(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $result = (new GameService())->battle($userId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function upgrade(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $stat = (string) ($_POST['stat'] ?? 'strength');
        $result = (new GameService())->upgradeStat($userId, $stat);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function marketCreate(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $resourceId = (int) ($_POST['resource_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $price = (float) ($_POST['price_per_unit'] ?? 0);
        $result = (new GameService())->createMarketOffer($userId, $resourceId, $quantity, $price);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function marketBuy(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $offerId = (int) ($_POST['offer_id'] ?? 0);
        $result = (new GameService())->buyMarketOffer($userId, $offerId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }
    public function factoryCreate(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $factoryTypeId = (int) ($_POST['factory_type_id'] ?? 0);
        $result = (new GameService())->createFactory($userId, $factoryTypeId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function factoryProduce(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $factoryId = (int) ($_POST['factory_id'] ?? 0);
        $result = (new GameService())->produceFactory($userId, $factoryId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function warStart(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $defenderCountryId = (int) ($_POST['defender_country_id'] ?? 0);
        $result = (new GameService())->startWar($userId, $defenderCountryId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function warAttack(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $warId = (int) ($_POST['war_id'] ?? 0);
        $result = (new GameService())->warAttack($userId, $warId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function partyCreate(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $name = (string) ($_POST['name'] ?? '');
        $ideology = (string) ($_POST['ideology'] ?? '');
        $result = (new GameService())->createParty($userId, $name, $ideology);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function partyJoin(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $partyId = (int) ($_POST['party_id'] ?? 0);
        $result = (new GameService())->joinParty($userId, $partyId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function partyLeave(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $result = (new GameService())->leaveParty($userId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function electionOpen(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $result = (new GameService())->openElection($userId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function electionVote(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $electionId = (int) ($_POST['election_id'] ?? 0);
        $partyId = (int) ($_POST['party_id'] ?? 0);
        $result = (new GameService())->voteElection($userId, $electionId, $partyId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function lawPropose(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $title = (string) ($_POST['title'] ?? '');
        $body = (string) ($_POST['body'] ?? '');
        $result = (new GameService())->proposeLaw($userId, $title, $body);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function lawVote(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $lawId = (int) ($_POST['law_id'] ?? 0);
        $vote = (string) ($_POST['vote'] ?? '');
        $result = (new GameService())->voteLaw($userId, $lawId, $vote);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function governmentAssignRole(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
        $roleKey = (string) ($_POST['role_key'] ?? '');
        $result = (new GameService())->assignGovernmentRole($userId, $targetUserId, $roleKey);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function governmentAction(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $actionKey = (string) ($_POST['action_key'] ?? '');
        $payload = [
            'buyer_tax_percent' => (float) ($_POST['buyer_tax_percent'] ?? 0),
            'seller_commission_percent' => (float) ($_POST['seller_commission_percent'] ?? 0),
            'score_to_win' => (int) ($_POST['score_to_win'] ?? 0),
        ];
        $result = (new GameService())->ministryAction($userId, $actionKey, $payload);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function travelRequestPermit(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $toCountryId = (int) ($_POST['to_country_id'] ?? 0);
        $result = (new GameService())->requestTravelPermit($userId, $toCountryId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function travelPermitDecision(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $permitId = (int) ($_POST['permit_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        $result = (new GameService())->decideTravelPermit($userId, $permitId, $decision);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function travelMove(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $cityId = (int) ($_POST['city_id'] ?? 0);
        $result = (new GameService())->travelToCity($userId, $cityId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function travelRequestCitizenship(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $toCountryId = (int) ($_POST['to_country_id'] ?? 0);
        $result = (new GameService())->requestCitizenship($userId, $toCountryId);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

    public function travelCitizenshipDecision(): void
    {
        $userId = Auth::userId();
        if (!$userId || !Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        $result = (new GameService())->decideCitizenship($userId, $requestId, $decision);
        Response::json($result, $result['ok'] ? 200 : 422);
    }

}
