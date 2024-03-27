<?php

declare(strict_types=1);

namespace Test\Unit;

use App\EventLoop;
use App\Exception\CardGame\AttemptOpenMissingCardException;
use App\Exception\CardGame\CardDeckNotExistException;
use App\Exception\CardGame\CardsNotSameSuitException;
use App\Exception\CardGame\CardsSameColorException;
use App\Exception\CardGame\CardsSameSuitException;
use App\Exception\CardGame\MainDeckClosedForShufflingException;
use App\Exception\CardGame\NotValidMoveException;
use App\Exception\CardGame\SectionNotExistException;
use App\Factory\DeckFactory\CardDecksFactory;
use App\GameObject\Card;
use App\GameObject\CardDeck;
use App\systems\CardGame;
use PHPUnit\Framework\TestCase;

class CardGameTest extends TestCase
{
    private CardGame $cardGame;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cardGame = $this->createCardGame($this->createFakeCardDecksFactory());
    }

    public function testThisSectionExistsIfPlayerChoseNonExistentFromSection(): void
    {
        $this->expectException(SectionNotExistException::class);
        $this->cardGame->thisSectionExists(['sectionFrom' => 4, 'sectionTo' => 1]);
    }

    public function testThisSectionExistsIfPlayerChoseNonExistentToSection(): void
    {
        $this->expectException(SectionNotExistException::class);
        $this->cardGame->thisSectionExists(['sectionFrom' => 1, 'sectionTo' => 0]);
    }

    public function testThisCardDeckExistsIfPlayerChoseTheMainDeckAndNonExistentDeckFrom(): void
    {
        $this->expectException(CardDeckNotExistException::class);
        $this->cardGame->thisCardDeckExists(['sectionFrom' => 1, 'numDeckFrom' => 2, 'sectionTo' => 2, 'numDeckTo' => 2]);
    }

    public function testThisCardDeckExistsIfPlayerChoseTheMainDeckAndNonExistentDeckTo(): void
    {
        $this->expectException(CardDeckNotExistException::class);
        $this->cardGame->thisCardDeckExists(['sectionFrom' => 1, 'numDeckFrom' => 1, 'sectionTo' => 2, 'numDeckTo' => 'Hello']);
    }

    public function testGetReversePlayerInput(): void
    {
        $reversePlayerInput = $this->cardGame->getReversePlayerInput(['sectionFrom' => 1, 'numDeckFrom' => 2, 'sectionTo' => 3, 'numDeckTo' => 4]);
        $this->assertEquals(
            ['sectionFrom' => 3, 'numDeckFrom' => 4, 'sectionTo' => 1, 'numDeckTo' => 2],
            $reversePlayerInput
        );
    }

    public function testAllCardsOpenIfAllCardsOpen(): void
    {
        $card = new Card('worms', 1, 'red', 'open');
        $cardDeck = [$card, $card, $card];
        $this->assertTrue($this->cardGame->allCardsAreOpen($cardDeck));
    }

    public function testAllCardsOpenIfOneCardClosed(): void
    {
        $card = new Card('worms', 1, 'red', 'open');
        $cardDeck = [
            new Card('worms', 1, 'red'),
            $card,
            $card
        ];
        $this->assertFalse($this->cardGame->allCardsAreOpen($cardDeck));
    }

    public function testCheckTheAbilityToMoveByColorCardsIfBadMoveOnColumnsCardDeck(): void
    {
        $this->expectException(CardsSameColorException::class);
        $this->cardGame->checkTheAbilityToMoveByColorCards(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 2, 'numDeckTo' => 1]);
    }

    public function testCheckTheAbilityToMoveByColorCardsIfGoodMoveOnColumnsCardDeck(): void
    {
        try {
            $this->cardGame->checkTheAbilityToMoveByColorCards(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 2, 'numDeckTo' => 2]);
        } catch (CardsSameColorException $e) {
            $this->fail();
        }
        $this->assertTrue(TRUE);
    }

    public function testCheckTheAbilityToMoveByColorCardsIfBadMoveOnWinningCardDeck(): void
    {
        $this->expectException(CardsNotSameSuitException::class);
        $this->cardGame->checkTheAbilityToMoveByColorCards(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 3, 'numDeckTo' => 3]);
    }

    public function testCheckTheAbilityToMoveByColorCardsIfGoodMoveOnWinningCardDeck(): void
    {
        try {
            $this->cardGame->checkTheAbilityToMoveByColorCards(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 3, 'numDeckTo' => 2]);
        } catch (CardsNotSameSuitException $e) {
            $this->fail();
        }
        $this->assertTrue(TRUE);
    }

    public function testCheckTheAbilityToMoveByCardSuitIfBadMoveOnColumnsCardDeck(): void
    {
        $this->expectException(CardsSameSuitException::class);
        $this->cardGame->checkTheAbilityToMoveByCardSuit(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 2, 'numDeckTo' => 1]);
    }

    public function testCheckTheAbilityToMoveByCardSuitIfGoodMoveOnColumnsCardDeck(): void
    {
        try {
            $this->cardGame->checkTheAbilityToMoveByCardSuit(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 2, 'numDeckTo' => 2]);
        } catch (CardsNotSameSuitException $e) {
            $this->fail();
        }
        $this->assertTrue(TRUE);
    }

    public function testCheckTheAbilityToMoveByCardSuitIfBadMoveOnWinningCardDeck(): void
    {
        $this->expectException(CardsNotSameSuitException::class);
        $this->cardGame->checkTheAbilityToMoveByCardSuit(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 3, 'numDeckTo' => 2]);
    }

    public function testCheckTheAbilityToMoveByCardSuitIfGoodMoveOnWinningCardDeck(): void
    {
        try {
            $this->cardGame->checkTheAbilityToMoveByCardSuit(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 3, 'numDeckTo' => 1]);
        } catch (CardsNotSameSuitException $e) {
            $this->fail();
        }
        $this->assertTrue(TRUE);
    }

    public function testCheckThePossibilityOfMovingByCardNumbersIfBadMoveOnSectionColumnsCardDeck(): void
    {
        $this->expectException(NotValidMoveException::class);
        $this->cardGame->checkThePossibilityOfMovingByCardNumbers(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 2, 'numDeckTo' => 3]);
    }

    public function testCheckThePossibilityOfMovingByCardNumbersIfGoodMoveOnSectionColumnsCardDeck(): void
    {
        try {
            $this->cardGame->checkThePossibilityOfMovingByCardNumbers(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 2, 'numDeckTo' => 2]);
        } catch (NotValidMoveException $e) {
            $this->fail();
        }
        $this->assertTrue(TRUE);
    }

    public function testCheckThePossibilityOfMovingByCardNumbersIfBadMoveOnSectionWinningCardDeck(): void
    {
        $this->expectException(NotValidMoveException::class);
        $this->cardGame->checkThePossibilityOfMovingByCardNumbers(['sectionFrom' => 2, 'numDeckFrom' => 2, 'sectionTo' => 3, 'numDeckTo' => 1]);
    }

    public function testCheckThePossibilityOfMovingByCardNumbersIfGoodMoveOnSectionWinningCardDeck(): void
    {
        try {
            $this->cardGame->checkThePossibilityOfMovingByCardNumbers(['sectionFrom' => 2, 'numDeckFrom' => 1, 'sectionTo' => 3, 'numDeckTo' => 1]);
        } catch (NotValidMoveException $e) {
            $this->fail();
        }
        $this->assertTrue(TRUE);
    }

    public function testGetCardOnPlayerInputTo(): void
    {
        $card = $this->cardGame->getCardOnPlayerInputTo(['sectionTo' => 2, 'numDeckTo' => 3]);
        $this->assertTrue($card->getTypeCard() === 'diamonds' && $card->getNumCard() === 2);
        $card = $this->cardGame->getCardOnPlayerInputTo(['sectionTo' => 3, 'numDeckTo' => 1]);
        $this->assertTrue($card->getTypeCard() === 'worms' && $card->getNumCard() === 14);
    }

    public function testGetCardOnPlayerInputFrom(): void
    {
        $card = $this->cardGame->getCardOnPlayerInputFrom(['sectionFrom' => 2, 'numDeckFrom' => 3, 'sectionTo' => 0]);
        $this->assertTrue($card->getTypeCard() === 'diamonds' && $card->getNumCard() === 2);
        $card = $this->cardGame->getCardOnPlayerInputFrom(['sectionFrom' => 3, 'numDeckFrom' => 1, 'sectionTo' => 0]);
        $this->assertTrue($card->getTypeCard() === 'worms' && $card->getNumCard() === 14);
    }

    public function testIsPossibleToGetCardFromMainDeckIfGoodMove(): void
    {
        try {
            $this->cardGame->isPossibleToGetCardFromMainDeck();
        } catch (AttemptOpenMissingCardException $e) {
            $this->fail();
        }
        $this->assertTrue(TRUE);
    }

    public function testIsPossibleToGetCardFromMainDeckIfBadMove(): void
    {
        $this->expectException(AttemptOpenMissingCardException::class);
        $this->cardGame->openCardInMainDeck();
        $this->cardGame->isPossibleToGetCardFromMainDeck();
    }

    public function testCheckPossibilityOfClosingMainDeckOfCardsIfGoodMove(): void
    {
        $this->expectException(MainDeckClosedForShufflingException::class);
        $this->cardGame->checkPossibilityOfClosingMainDeckOfCards();
    }

    public function testCheckPossibilityOfClosingMainDeckOfCardsIfBadMove(): void
    {
        try {
            $this->cardGame->openCardInMainDeck();
            $this->cardGame->checkPossibilityOfClosingMainDeckOfCards();
        } catch (MainDeckClosedForShufflingException $e) {
            $this->fail();
        }
        $this->assertTrue(TRUE);
    }

    public function createFakeCardDecksFactory(): CardDecksFactory
    {
        $fakeColumnsCardDeck = new CardDeck('columnsCardDeck');
        $fakeColumnsCardDeck->addCards([new Card('worms', 2, 'red', 'open')]);
        $fakeColumnsCardDeck->addCards([new Card('diamonds', 3, 'black', 'open')]);
        $fakeColumnsCardDeck->addCards([new Card('diamonds', 2, 'red', 'open')]);
        $fakeWinningCardDeck = new CardDeck('winningCardDeck');
        $fakeWinningCardDeck->addCards([new Card('worms', 14, 'red', 'open')]);
        $fakeWinningCardDeck->addCards([new Card('diamonds', 1, 'red', 'open')]);
        $fakeWinningCardDeck->addCards([new Card('clubs', 14, 'black', 'open')]);
        $fakeMainCardDeck = new CardDeck('mainCardDeck');
        $fakeMainCardDeck->addCards(new Card('clubs', 14, 'black'));
        $fakeCardDeckFactory = $this->getMockBuilder(CardDecksFactory::class)->getMock();
        $fakeCardDeckFactory
            ->method('build')
            ->willReturn([
                'mainCardDeck' => $fakeMainCardDeck,
                'columnsCardDeck' => $fakeColumnsCardDeck,
                'winningCardDeck' => $fakeWinningCardDeck
            ]);
        return $fakeCardDeckFactory;
    }

    public function createCardGame(CardDecksFactory $cardDecksFactory): CardGame
    {
        $cardGame = new CardGame($cardDecksFactory);
        $cardGame->setEventPusher(new EventLoop());
        $cardGame->initializeGameState();
        return $cardGame;
    }
}