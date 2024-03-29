<?php

declare(strict_types=1);

namespace App\systems;

use App\Events\AddInputErrorMessage;
use App\Events\AddMoveHelpMessageOnMainScreen;
use App\Events\CheckUserInputEvent;
use App\Events\CreateImagesPlayingCards;
use App\Events\DisplayMainScreen;
use App\Events\DisplayMainScreenWithFirstInput;
use App\Events\DisplayMainScreenWithFourthInput;
use App\Events\DisplayMainScreenWithSecondInput;
use App\Events\DisplayMainScreenWithThirdInput;
use App\Events\GameOver;
use App\Events\StartGame;
use App\Events\UpdateGameState;
use App\Events\UpdateMainScreen;
use App\Exception\CardGame\AttemptOpenMissingCardException;
use App\Exception\CardGame\CardsNotSameSuitException;
use App\Exception\CardGame\CardsSameColorException;
use App\Exception\CardGame\CardsSameSuitException;
use App\Exception\CardGame\MainDeckClosedForShufflingException;
use App\Exception\CardGame\NotValidMoveException;
use App\Exception\CardGame\PlayingCardMissingException;
use App\Exception\CardGame\SectionNotExistException;
use App\Factory\DeckFactory\CardDecksFactoryInterface;
use App\GameObject\Card;
use Exception;

class CardGame extends AbstractSystem
{
    private CardDecksFactoryInterface $cardDecksFactory;

    private array $gameData = [
        'gameState' => [],
        'countPlayerMoves' => 0,
        'historyPlayerInput' => []
    ];

    private int $inputCounter = 0;

    private array $playerMoves = [];

    const
        SECTION_MAIN_CARD_DECK = 1,
        SECTION_COLUMN_CARDS = 2,
        SECTION_WINNING_CARD_DECK = 3;

    private array $cardDeckSectionNames = [
        self::SECTION_MAIN_CARD_DECK => 'mainCardDeck',
        self::SECTION_COLUMN_CARDS => 'columnsCardDeck',
        self::SECTION_WINNING_CARD_DECK => 'winningCardDeck'
    ];

    private array $numberOfDecksInSection = [
        self::SECTION_MAIN_CARD_DECK => 1,
        self::SECTION_COLUMN_CARDS => 7,
        self::SECTION_WINNING_CARD_DECK => 4
    ];

    public function __construct(CardDecksFactoryInterface $cardDecksFactory)
    {
        $this->cardDecksFactory = $cardDecksFactory;
    }

    public function getBinds(): array
    {
        return [
            'O' => fn() => $this->openCardInMainDeck(),
            'R' => fn() => $this->closeMainDeckOfCards(),
            'H' => fn() => $this->findPossibleMove(),
            'C' => fn() => $this->cancelLastMove()
        ];
    }
    public function getSubscriptions(): array
    {
        return [
            StartGame::class => fn() => $this->initializeGameState(),
            UpdateGameState::class => function (UpdateGameState $event) {
                $this->updateGameState($event->getGameData());
            },
            CheckUserInputEvent::class => function (CheckUserInputEvent $event) {
                $this->moveManager($event->getGameData()[0]);
            }
        ];
    }

    public function initializeGameState(): void
    {
        $this->createCardDecks();
        $event1 = new CreateImagesPlayingCards();
        $event1->setGameData($this->gameData);
        $event2 = new UpdateMainScreen();
        $event2->setGameData($this->gameData);
        $event3 = new DisplayMainScreen();
        $event4 = new DisplayMainScreenWithFirstInput();
        $this->eventPusher->push($event1, $event2, $event3, $event4);
    }

    public function createCardDecks(): void
    {
        $cardDecks = $this->cardDecksFactory->build();
        $this->gameData['gameState'][$cardDecks['mainCardDeck']->getTypeDeck()] = $cardDecks['mainCardDeck']->getCards();
        $this->gameData['gameState'][$cardDecks['columnsCardDeck']->getTypeDeck()] = $cardDecks['columnsCardDeck']->getCards();
        $this->gameData['gameState'][$cardDecks['winningCardDeck']->getTypeDeck()] = $cardDecks['winningCardDeck']->getCards();
    }

    public function updateGameState(array $playerInput): void
    {
        try {
            if (is_string($playerInput[0])) {
                $this->bindHandler($playerInput[0]);
            } else {
                $this->thisSectionExists($playerInput);
                $this->thisCardDeckExists($playerInput);
                $this->playingCardMissing($playerInput);
                $card = $this->getCardOnPlayerInputFrom($playerInput);
                $cardOnWhichToPut = $this->getCardOnPlayerInputTo($playerInput);
                if (!$this->isCardOpen($card) || !$this->isCardOpen($cardOnWhichToPut)) {
                    throw new NotValidMoveException();
                }
                $this->checkThePossibilityOfMovingByCardNumbers($playerInput);
                $this->checkTheAbilityToMoveByCardSuit($playerInput);
                $this->checkTheAbilityToMoveByColorCards($playerInput);
                $cards = $this->pickUpTheCard($playerInput);
                $playerInput['numberOfMovedCards'] = count($cards);
                $this->addCard($playerInput, $cards);
                if ($playerInput['sectionFrom'] === self::SECTION_COLUMN_CARDS) {
                    $this->openCardsInColumnsCardDeck($playerInput);
                }
                if ($this->isGameOver()) {
                    $this->addOneMove();
                    $event1 = new UpdateMainScreen();
                    $event1->setGameData($this->gameData);
                    $event2 = new GameOver();
                    $event2->setGameData($this->gameData);
                    $event3 = new DisplayMainScreen();
                    $this->eventPusher->push($event1, $event2, $event3);
                } else {
                    $this->addOneMove();
                    $this->addInputInHistory($playerInput);
                    $event1 = new UpdateMainScreen();
                    $event1->setGameData($this->gameData);
                    $event2 = new DisplayMainScreen();
                    $event3 = new DisplayMainScreenWithFirstInput();
                    $this->eventPusher->push($event1, $event2, $event3);
                }
            }
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }
    }

    public function exceptionHandler(Exception $e): void
    {
        $message = $e->getMessage();
        $event1 = new UpdateMainScreen();
        $event1->setGameData($this->gameData);
        $event2 = new AddInputErrorMessage();
        $event2->setGameData([$message]);
        $event3 = new DisplayMainScreen();
        $event4 = new DisplayMainScreenWithFirstInput();
        $this->eventPusher->push($event1, $event2, $event3, $event4);
    }

    public function addOneMove(): void
    {
        $this->gameData['countPlayerMoves']++;
    }

    public function runMethodOnBind(string $bindEnteredByThePlayer): void
    {
        $binds = $this->getBinds();
        $handlerBind = $binds[$bindEnteredByThePlayer];
        $handlerBind();
        if ($bindEnteredByThePlayer !== 'H') {
            $event1 = new UpdateMainScreen();
            $event1->setGameData($this->gameData);
            $event2 = new DisplayMainScreen();
            $event3 = new DisplayMainScreenWithFirstInput();
            $this->eventPusher->push($event1, $event2, $event3);
        }
    }
    /**
     * @throws Exception
     */
    public function checkPossibilityOfClosingMainDeckOfCards(): void
    {
        $openCardCounter = 0;
        foreach ($this->gameData['gameState']['mainCardDeck'] as $card) {
            if ($card->getCardPosition() === 'open') {
                $openCardCounter++;
            }
        }
        if ($openCardCounter !== count($this->gameData['gameState']['mainCardDeck'])) {
            throw new MainDeckClosedForShufflingException();
        }
    }

    public function addInputInHistory(array|string $move): void
    {
        $this->gameData['historyPlayerInput'][] = $move;

    }

    /**
     * @throws Exception
     */
    public function isPossibleToGetCardFromMainDeck(): void
    {
        foreach ($this->gameData['gameState']['mainCardDeck'] as $card) {
            if ($card->getCardPosition() === 'closed') {
                return;
            }
        }
        throw new AttemptOpenMissingCardException();
    }

    public function closeMainDeckOfCards(): void
    {
        $mainCardDeck = $this->gameData['gameState']['mainCardDeck'];
        $closedMainCardDeck = [];
        foreach ($mainCardDeck as $card) {
            $card->setCardPosition('closed');
            $closedMainCardDeck[] = $card;
        }
        $this->gameData['gameState']['mainCardDeck'] = $closedMainCardDeck;
        $this->addOneMove();
    }

    public function openCardsInColumnsCardDeck(array $playerInput): void
    {
        $keyCardDeckInSection = $playerInput['numDeckFrom'] - 1;
        if (empty($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection])) {
            return;
        }
        if (!$this->isCardOpen(end($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection]))) {
            end($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection])->setCardPosition('open');
        }
    }

    public function addCard(array $playerInput, array $cards): void
    {
        $keyCardDeckInSection = $playerInput['numDeckTo'] - 1;
        if ($playerInput['sectionTo'] == self::SECTION_COLUMN_CARDS) {
            foreach ($cards as $oneCard) {
                $this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionTo']]][$keyCardDeckInSection][] = $oneCard;
            }
        }
        if ($playerInput['sectionTo'] == self::SECTION_WINNING_CARD_DECK) {
            if ($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionTo']]][$keyCardDeckInSection][0] === NULL) {
                $this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionTo']]][$keyCardDeckInSection][0] = $cards[0];
            } else {
                $this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionTo']]][$keyCardDeckInSection][] = $cards[0];
            }
        }
        if ($playerInput['sectionTo'] == self::SECTION_MAIN_CARD_DECK) {
            array_unshift($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionTo']]], $cards[0]);
        }
    }


    public function pickUpTheCard(array $playerInput): array
    {
        $keyCardDeckInSection = $playerInput['numDeckFrom'] - 1;
        if ($playerInput['sectionFrom'] == self::SECTION_MAIN_CARD_DECK) {
            return [array_shift($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]])];
        }
        if ($playerInput['sectionFrom'] == self::SECTION_COLUMN_CARDS) {
            $openCardsCounter = 0;
            foreach ($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection] as $card) {
                if ($this->isCardOpen($card) && isset($card)) {
                    $openCardsCounter++;
                }
            }
            if ($openCardsCounter === 1 || $playerInput['sectionTo'] == self::SECTION_WINNING_CARD_DECK) {
                return [array_pop($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection])];
            } else {
                $numFirstOpenCardInColumn = count($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection]) - $openCardsCounter;
                $cards = [];
                $this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection] = array_values($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection]);
                for ($i = 1; $i <= $openCardsCounter; $i++) {
                    $cards[] = $this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection][$numFirstOpenCardInColumn];
                    unset($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection][$numFirstOpenCardInColumn]);
                    $numFirstOpenCardInColumn++;
                }
                return $cards;
            }
        }
        if ($playerInput['sectionFrom'] == self::SECTION_WINNING_CARD_DECK) {
            return [array_pop($this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]][$keyCardDeckInSection])];
        }
        return [];
    }

    public function getCardOnPlayerInputTo(array $playerInput): ?object // получить карту на которую ставим
    {
        $cardDeckTo = $this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionTo']]];
        if (empty($cardDeckTo[$playerInput['numDeckTo'] - 1])) {
            return NULL;
        }
        if ($playerInput['sectionTo'] == self::SECTION_COLUMN_CARDS) {
            return end($cardDeckTo[$playerInput['numDeckTo'] - 1]);
        }
        if ($playerInput['sectionTo'] == self::SECTION_WINNING_CARD_DECK) {
            return end($cardDeckTo[$playerInput['numDeckTo'] - 1]);
        }
        return NULL;
    }

    public function getCardOnPlayerInputFrom(array $playerInput): ?object // получить карту, которую будут использовать
    {
        $sectionFrom = $this->gameData['gameState'][$this->cardDeckSectionNames[$playerInput['sectionFrom']]];
        if ($playerInput['sectionFrom'] == self::SECTION_MAIN_CARD_DECK) {
            return $sectionFrom[0];
        }
        if ($playerInput['sectionFrom'] == self::SECTION_COLUMN_CARDS) {
            $sectionFrom[$playerInput['numDeckFrom'] - 1] = array_values($sectionFrom[$playerInput['numDeckFrom'] - 1]);
            $openCardsCounter = 0;
            if ($playerInput['sectionTo'] == self::SECTION_WINNING_CARD_DECK && !empty($sectionFrom[$playerInput['numDeckFrom'] - 1])) {
                return end($sectionFrom[$playerInput['numDeckFrom'] - 1]);
            }
            foreach ($sectionFrom[$playerInput['numDeckFrom'] - 1] as $card) {
                if ($this->isCardOpen($card)) {
                    $openCardsCounter++;
                }
            }
            if ($openCardsCounter === 1) {
                return end($sectionFrom[$playerInput['numDeckFrom'] - 1]);
            } else {
                $numFirstOpenCardInColumn = count($sectionFrom[$playerInput['numDeckFrom'] - 1]) - $openCardsCounter;
                return $sectionFrom[$playerInput['numDeckFrom'] - 1][$numFirstOpenCardInColumn];
            }
        }
        if ($playerInput['sectionFrom'] == self::SECTION_WINNING_CARD_DECK) {
            return end($sectionFrom[$playerInput['numDeckFrom'] - 1]);
        }
        return NULL;
    }

    public function isCardOpen(Card|null $card): bool
    {
        if ($card === NULL) {
            return true;
        }
        if ($card->getCardPosition() == 'closed') {
            return false;
        }
        return true;
    }
    /**
     * @throws NotValidMoveException
     */
    public function checkThePossibilityOfMovingByCardNumbers(array $playerInput): void
    {
        $card = $this->getCardOnPlayerInputFrom($playerInput);
        $cardOnWhichToPut = $this->getCardOnPlayerInputTo($playerInput);

        if ($playerInput['sectionTo'] == self::SECTION_MAIN_CARD_DECK) {
            throw new NotValidMoveException();
        }

        if ($cardOnWhichToPut === NULL) {
            if ($card->getNumCard() === 13 && $playerInput['sectionTo'] == self::SECTION_COLUMN_CARDS) {
                return;
            }
            if ($card->getNumCard() === 14 && $playerInput['sectionTo'] == self::SECTION_WINNING_CARD_DECK) {
                return;
            }
            if ($card->getNumCard() !== 14 && $playerInput['sectionTo'] == self::SECTION_WINNING_CARD_DECK) {
                throw new NotValidMoveException();
            }
            if ($card->getNumCard() !== 13 && $playerInput['sectionTo'] == self::SECTION_COLUMN_CARDS) {
                throw new NotValidMoveException();

            }
        }

        if ($card->getNumCard() === $cardOnWhichToPut->getNumCard()) {
            throw new NotValidMoveException();
        }

        if ($playerInput['sectionTo'] == self::SECTION_COLUMN_CARDS) {

            if ($card->getNumCard() >= $cardOnWhichToPut->getNumCard() || $card->getNumCard() < $cardOnWhichToPut->getNumCard() - 1 || empty($cardOnWhichToPut)) {
                throw new NotValidMoveException();
            }
            if ($card->getNumCard() == 13 && $cardOnWhichToPut->getNumCard() == 14) {
                throw new NotValidMoveException();
            }

        }

        if ($playerInput['sectionTo'] == self::SECTION_WINNING_CARD_DECK) {
            if ($cardOnWhichToPut->getNumCard() === 14 && $card->getNumCard() != 2 && $cardOnWhichToPut->getNumCard() != 2) {
                throw new NotValidMoveException();
            }
            if ($card->getNumCard() - 1 != $cardOnWhichToPut->getNumCard() && $cardOnWhichToPut->getNumCard() != 14) {
                throw new NotValidMoveException();
            }
        }

    }

    /**
     * @throws Exception
     */
    public function checkTheAbilityToMoveByCardSuit(array $playerInput): void
    {
        $card = $this->getCardOnPlayerInputFrom($playerInput);
        $cardOnWhichToPut = $this->getCardOnPlayerInputTo($playerInput);
        if ($cardOnWhichToPut === NULL) {
            return;
        }
        if ($card->getTypeCard() != $cardOnWhichToPut->getTypeCard() && $playerInput['sectionTo'] === self::SECTION_WINNING_CARD_DECK) {
            throw new CardsNotSameSuitException();
        }
        if ($card->getTypeCard() === $cardOnWhichToPut->getTypeCard() && $playerInput['sectionTo'] === self::SECTION_COLUMN_CARDS) {
            throw new CardsSameSuitException();
        }
    }

    /**
     * @throws CardsSameColorException
     */
    public function checkTheAbilityToMoveByColorCards(array $playerInput): void
    {
        $card = $this->getCardOnPlayerInputFrom($playerInput);
        $cardOnWhichToPut = $this->getCardOnPlayerInputTo($playerInput);
        if ($cardOnWhichToPut === NULL) {
            return;
        }
        if ($card->getColor() === $cardOnWhichToPut->getColor() && $playerInput['sectionTo'] != self::SECTION_WINNING_CARD_DECK) {
            throw new CardsSameColorException();
        }

    }

    public function openCardInMainDeck(): void
    {
        $card = array_pop($this->gameData['gameState']['mainCardDeck']);
        $card->setCardPosition('open');
        array_unshift($this->gameData['gameState']['mainCardDeck'], $card);
        $this->addOneMove();
        $this->addInputInHistory('O');
    }

    /**
     * @throws Exception
     */
    public function findPossibleMove(): void
    {
        $mainCardDeck = $this->gameData['gameState']['mainCardDeck'];
        $columnsCardDeck = $this->gameData['gameState']['columnsCardDeck'];
        $winningCardDecks = $this->gameData['gameState']['winningCardDeck'];
        $counterNumDeckInSectionTo = 0;
        $counterNumDeckInSectionFrom = 0;
        $moveHelp['sectionFrom'] = 1;
        $moveHelp['numDeckFrom'] = 1;
        $moveHelp['sectionTo'] = 0;
        $moveHelp['numDeckTo'] = 0;

        if ($this->isCardOpen($this->getCardOnPlayerInputFrom($moveHelp)) && !empty($mainCardDeck)) {
            foreach ($columnsCardDeck as $columnCardDeck) {
                $counterNumDeckInSectionTo++;
                try {
                    if ($this->isCardOpen($mainCardDeck[0])) {
                        $moveHelp['sectionTo'] = 2;
                        $moveHelp['numDeckTo'] = $counterNumDeckInSectionTo;
                        $this->checkThePossibilityOfMovingByCardNumbers($moveHelp);
                        $this->checkTheAbilityToMoveByCardSuit($moveHelp);
                        $this->checkTheAbilityToMoveByColorCards($moveHelp);
                        $this->addOneMove();
                        $event1 = new UpdateMainScreen();
                        $event1->setGameData($this->gameData);
                        $event2 = new AddMoveHelpMessageOnMainScreen();
                        $event2->setGameData($moveHelp);
                        $event3 = new DisplayMainScreen();
                        $event4 = new DisplayMainScreenWithFirstInput();
                        $this->eventPusher->push($event1, $event2, $event3, $event4);
                        return;
                    }
                } catch (Exception $message) {
                    continue;
                }
            }

            $counterNumDeckInSectionTo = 0;
            foreach ($winningCardDecks as $winningCardDeck) {
                $counterNumDeckInSectionTo++;
                try {
                    $moveHelp['sectionFrom'] = 1;
                    $moveHelp['numDeckFrom'] = 1;
                    $moveHelp['sectionTo'] = 3;
                    $moveHelp['numDeckTo'] = $counterNumDeckInSectionTo;
                    $this->checkThePossibilityOfMovingByCardNumbers($moveHelp);
                    $this->checkTheAbilityToMoveByCardSuit($moveHelp);
                    $this->checkTheAbilityToMoveByColorCards($moveHelp);
                    $this->addOneMove();
                    $event1 = new UpdateMainScreen();
                    $event1->setGameData($this->gameData);
                    $event2 = new AddMoveHelpMessageOnMainScreen();
                    $event2->setGameData($moveHelp);
                    $event3 = new DisplayMainScreen();
                    $event4 = new DisplayMainScreenWithFirstInput();
                    $this->eventPusher->push($event1, $event2, $event3, $event4);
                    return;
                } catch (Exception $message) {
                    continue;
                }
            }
        }
        $counterNumDeckInSectionTo = 0;
        $moveHelp['sectionFrom'] = 2;
        $moveHelp['sectionTo'] = 2;
        foreach ($columnsCardDeck as $columnCardDeckFrom) {
            $counterNumDeckInSectionFrom++;
            $counterNumDeckInSectionTo = 0;
            if (empty($columnCardDeckFrom)) {
                continue;
            }
            foreach ($columnsCardDeck as $columnCardDeckTo) {
                $counterNumDeckInSectionTo++;
                if (!empty($columnCardDeckFrom) && $this->allCardsAreOpen($columnCardDeckFrom) && empty($columnCardDeckTo)) {
                    continue;
                }
                try {
                    $moveHelp['numDeckFrom'] = $counterNumDeckInSectionFrom;
                    $moveHelp['numDeckTo'] = $counterNumDeckInSectionTo;
                    $this->checkThePossibilityOfMovingByCardNumbers($moveHelp);
                    $this->checkTheAbilityToMoveByCardSuit($moveHelp);
                    $this->checkTheAbilityToMoveByColorCards($moveHelp);
                    $this->addOneMove();
                    $event1 = new UpdateMainScreen();
                    $event1->setGameData($this->gameData);
                    $event2 = new AddMoveHelpMessageOnMainScreen();
                    $event2->setGameData($moveHelp);
                    $event3 = new DisplayMainScreen();
                    $event4 = new DisplayMainScreenWithFirstInput();
                    $this->eventPusher->push($event1, $event2, $event3, $event4);
                    return;
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        $counterNumDeckInSectionFrom = 0;
        $counterNumDeckInSectionTo = 0;
        $moveHelp['sectionFrom'] = 2;
        $moveHelp['sectionTo'] = 3;

        foreach ($columnsCardDeck as $columnCardDeckFrom) {
            $counterNumDeckInSectionFrom++;
            $counterNumDeckInSectionTo = 0;
            if (empty($columnCardDeckFrom)) {
                continue;
            }
            foreach ($winningCardDecks as $winningCardDeck) {
                $counterNumDeckInSectionTo++;
                try {
                    $moveHelp['numDeckFrom'] = $counterNumDeckInSectionFrom;
                    $moveHelp['numDeckTo'] = $counterNumDeckInSectionTo;
                    $this->checkThePossibilityOfMovingByCardNumbers($moveHelp);
                    $this->checkTheAbilityToMoveByCardSuit($moveHelp);
                    $this->checkTheAbilityToMoveByColorCards($moveHelp);
                    $this->addOneMove();
                    $event1 = new UpdateMainScreen();
                    $event1->setGameData($this->gameData);
                    $event2 = new AddMoveHelpMessageOnMainScreen();
                    $event2->setGameData($moveHelp);
                    $event3 = new DisplayMainScreen();
                    $event4 = new DisplayMainScreenWithFirstInput();
                    $this->eventPusher->push($event1, $event2, $event3, $event4);
                    return;
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        $event1 = new UpdateMainScreen();
        $event1->setGameData($this->gameData);
        $event2 = new AddInputErrorMessage();
        $event2->setGameData(['Not found possibility move!']);
        $event3 = new DisplayMainScreen();
        $event4 = new DisplayMainScreenWithFirstInput();
        $this->eventPusher->push($event1, $event2, $event3, $event4);
    }
    public function allCardsAreOpen(array $cardDeck): bool
    {
        foreach ($cardDeck as $card) {
            if (!$this->isCardOpen($card)) {
                return false;
            }
        }
        return true;
    }

    public function pickUpTheCardForCancelLastMove(array $lastMove): array
    {
        $cards = [];
        $reversePlayerInput = $this->getReversePlayerInput($lastMove);
        $keyCardDeckInSection = $reversePlayerInput['numDeckFrom'] - 1;
        if ($lastMove['sectionFrom'] == self::SECTION_COLUMN_CARDS) {
            for ($i = 0; $i < $lastMove['numberOfMovedCards']; $i++) {
                $cards[] = array_pop($this->gameData['gameState']['columnsCardDeck'][$keyCardDeckInSection]);
            }
            return $cards;
        }
        if ($lastMove['sectionFrom'] == self::SECTION_MAIN_CARD_DECK) {
            return [array_pop($this->gameData['gameState']['columnsCardDeck'][$keyCardDeckInSection])];
        }
        return [];
    }

    /**
     * @throws NotValidMoveException
     * @throws Exception
     */
    public function bindHandler(string $playerBindInput): void
    {
        if ($playerBindInput !== 'O'
            && $playerBindInput !== 'R'
            && $playerBindInput !== 'C'
            && $playerBindInput !== 'H') {
            throw new NotValidMoveException();
        }
        if ($playerBindInput === 'R') {
            $this->checkPossibilityOfClosingMainDeckOfCards();
        }
        if ($playerBindInput === 'O') {
            $this->isPossibleToGetCardFromMainDeck();
        }
        $this->runMethodOnBind($playerBindInput);
    }

    public function getReversePlayerInput(array $playerInput): array
    {
        $sectionFrom = $playerInput['sectionFrom'];
        $numDeckFrom = $playerInput['numDeckFrom'];
        $sectionTo = $playerInput['sectionTo'];
        $numDeckTo = $playerInput['numDeckTo'];
        return ['sectionFrom' => $sectionTo, 'numDeckFrom' => $numDeckTo, 'sectionTo' => $sectionFrom, 'numDeckTo' => $numDeckFrom];
    }

    public function cancelLastMove(): void
    {
        $lastMove = end($this->gameData['historyPlayerInput']);
        if (empty($lastMove)) {
            return;
        }
        if ($lastMove === 'O') {
            $card = array_shift($this->gameData['gameState']['mainCardDeck']);
            $card->setCardPosition('closed');
            $this->gameData['gameState']['mainCardDeck'][] = $card;
        }
        if (is_array($lastMove)) {
            $sectionFrom = $lastMove['sectionFrom'];
            $sectionTo = $lastMove['sectionTo'];
            $reversePlayerInput = $this->getReversePlayerInput($lastMove);
            if ($sectionFrom == self::SECTION_COLUMN_CARDS || $sectionFrom == self::SECTION_MAIN_CARD_DECK) {
                if ($sectionTo == self::SECTION_COLUMN_CARDS) {
                    $cards = $this->pickUpTheCardForCancelLastMove($lastMove);
                    $this->addCard($reversePlayerInput, $cards);
                }
                if ($sectionTo == self::SECTION_WINNING_CARD_DECK) {
                    $cards = $this->pickUpTheCard($reversePlayerInput);
                    $this->addCard($reversePlayerInput, $cards);
                }
                if ($sectionFrom == self::SECTION_COLUMN_CARDS) {
                    $this->returnPositionOfCardsAfterCancelMove($lastMove);
                }
            }
            if ($sectionFrom == self::SECTION_WINNING_CARD_DECK) {
                $card = $this->pickUpTheCard($reversePlayerInput);
                $this->addCard($reversePlayerInput, $card);
            }
        }
        $this->clearLastPlayerMoveFromHistory();
        $this->addOneMove();
    }

    public function clearLastPlayerMoveFromHistory(): void
    {
        array_pop($this->gameData['historyPlayerInput']);
    }


    public function returnPositionOfCardsAfterCancelMove(array $playerInput): void
    {
        $sectionFrom = $this->gameData['gameState']['columnsCardDeck'][$playerInput['numDeckFrom'] - 1];
        $numberOfMovedCards = end($this->gameData['historyPlayerInput'])['numberOfMovedCards'];
        if (count($sectionFrom) > $numberOfMovedCards) {
            $cardKeyToClosed = count($sectionFrom) - $numberOfMovedCards - 1;
            $this->gameData['gameState']
            ['columnsCardDeck']
            [$playerInput['numDeckFrom'] - 1]
            [$cardKeyToClosed]->setCardPosition('closed');
        }
    }

    /**
     * @throws PlayingCardMissingException
     */
    public function playingCardMissing(array $gameData): void
    {
        $card = $this->getCardOnPlayerInputFrom($gameData);
        if (empty($card)) {
            throw new PlayingCardMissingException();
        }
    }
    /**
     * @throws SectionNotExistException
     */
    public function thisSectionExists(array $gameData): void
    {
        $sectionFrom = $gameData['sectionFrom'];
        $sectionTo = $gameData['sectionTo'];
        if ($sectionFrom < 1 || $sectionFrom > 3 || $sectionTo < 1 || $sectionTo > 3) {
            throw new SectionNotExistException();
        }
    }

    /**
     * @throws SectionNotExistException
     */
    public function thisCardDeckExists(array $gameData): void
    {
        if ($gameData['numDeckFrom'] > $this->numberOfDecksInSection[$gameData['sectionFrom']] || $gameData['numDeckFrom'] < 1) {
            throw new SectionNotExistException();
        }
        if ($gameData['numDeckTo'] > $this->numberOfDecksInSection[$gameData['sectionTo']] || $gameData['numDeckTo'] < 1) {
            throw new SectionNotExistException();
        }
    }

    public function moveManager(string $playerInput): void
    {
        try {
            if (!is_numeric($playerInput)) {
                $playerInput = strtoupper($playerInput);
                if ($playerInput === 'H' && $this->inputCounter === 0) {
                    $this->updateGameState(['H']);
                    return;
                } elseif ($playerInput === 'O' && $this->inputCounter === 0) {
                    $this->updateGameState(['O']);
                    return;
                } elseif ($playerInput === 'C' && $this->inputCounter === 0) {
                    $this->updateGameState(['C']);
                    return;
                } elseif ($playerInput === 'R' && $this->inputCounter === 0) {
                    $this->updateGameState(['R']);
                    return;
                } else {
                    throw new NotValidMoveException();
                }
            }
            $this->inputCounter++;
            if ($this->inputCounter === 1) {
                $event = new DisplayMainScreenWithSecondInput();
                $this->eventPusher->push($event);
                $this->playerMoves['sectionFrom'] = (int)$playerInput;
            }
            if ($this->inputCounter === 2) {
                $event = new DisplayMainScreenWithThirdInput();
                $this->eventPusher->push($event);
                $this->playerMoves['numDeckFrom'] = (int)$playerInput;
            }
            if ($this->inputCounter === 3) {
                $event = new DisplayMainScreenWithFourthInput;
                $this->eventPusher->push($event);
                $this->playerMoves['sectionTo'] = (int)$playerInput;
            }
            if ($this->inputCounter === 4) {
                $this->playerMoves['numDeckTo'] = (int)$playerInput;
                $event = new UpdateGameState();
                $event->setGameData($this->playerMoves);
                $this->eventPusher->push($event);
                $this->clearPlayerMoves();
                $this->clearInputCounter();
            }
        } catch (Exception $e) {
            $this->exceptionHandler($e);
        }

    }

    public function clearInputCounter(): void
    {
        $this->inputCounter = 0;
    }

    public function clearPlayerMoves(): void
    {
        $this->playerMoves = [];
    }

    public function isGameOver(): bool
    {
        $cardCounter = 0;
        foreach ($this->gameData['gameState']['winningCardDeck'] as $cardDeck) {
            $cardCounter += count($cardDeck);
        }
        return $cardCounter === 52;
    }
}