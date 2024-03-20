<?php

declare(strict_types=1);

namespace App\systems;

use App\Events\AddInputErrorMessage;
use App\Events\AddMoveHelpMessageOnMainScreen;
use App\Events\CreateImagesPlayingCards;
use App\Events\DisplayMainScreen;
use App\Events\DisplayMainScreenWithFirstInput;
use App\Events\DisplayMainScreenWithFourthInput;
use App\Events\DisplayMainScreenWithSecondInput;
use App\Events\DisplayMainScreenWithThirdInput;
use App\Events\GameOver;
use App\Events\InputEvent;
use App\Events\UpdateMainScreen;
use App\Factory\CardFactory\CardPictureFactory;
use App\GameObject\Card;
use App\Painter\Painter;
use App\Pictures\ClosedCard;
use App\Pictures\EmptyCell;

class GraphicSystem extends AbstractSystem
{
    const
        DISTANCE_BETWEEN_COLUMNS = 14,
        DISTANCE_BETWEEN_CARDS_IN_COLUMNS = 3;

    private array $mainScreen = [];

    private Painter $painter;

    private array $playingCardsImages;

    private CardPictureFactory $cardPictureFactory;

    public function __construct(CardPictureFactory $cardPictureFactory)
    {
        $this->cardPictureFactory = $cardPictureFactory;
        $this->painter = new Painter(161, 61);
        $this->addCanvasOnMainScreen();
    }

    public function getSubscriptions(): array
    {
        return [
            UpdateMainScreen::class => function (UpdateMainScreen $event) {
                $this->updateMainScreen($event->getGameData());
            },
            CreateImagesPlayingCards::class => function (CreateImagesPlayingCards $event) {
                $this->createImagesPlayingCards($event->getGameData());
            },
            DisplayMainScreen::class => fn() => $this->displayMainScreen(),
            DisplayMainScreenWithFirstInput::class => fn() => $this->displayMainScreenWithFirstInput(),
            DisplayMainScreenWithSecondInput::class => fn() => $this->displayMainScreenWithSecondInput(),
            DisplayMainScreenWithThirdInput::class => fn() => $this->displayMainScreenWithThirdInput(),
            DisplayMainScreenWithFourthInput::class => fn() => $this->displayMainScreenWithFourthInput(),
            AddInputErrorMessage::class => function (AddInputErrorMessage $event) {
                $this->addErrorMessageOnMainScreen($event->getGameData());
            },
            AddMoveHelpMessageOnMainScreen::class => function (AddMoveHelpMessageOnMainScreen $event) {
                $this->addMoveHelpMessageOnMainScreen($event->getGameData());
            },
            GameOver::class => function (GameOver $event) {
                $this->addMessageAboutVictory($event->getGameData());
            }
        ];
    }

    public function displayMainScreenWithFirstInput(): void
    {
        echo 'Enter the section from which you will pick up the card : ';
        $this->eventPusher->push(new InputEvent());
    }

    public function displayMainScreenWithSecondInput(): void
    {
        echo 'Enter the deck with which you will pick up the card : ';
        $this->eventPusher->push(new InputEvent());
    }

    public function displayMainScreenWithThirdInput(): void
    {
        echo 'Enter the section in which you want to put the card: ';
        $this->eventPusher->push(new InputEvent());
    }

    public function displayMainScreenWithFourthInput(): void
    {
        echo 'Enter the deck in which you want to put the card : ';
        $this->eventPusher->push(new InputEvent());
    }


    public function createImagesPlayingCards(array $gameData): void
    {
        $mainCardDeck = $gameData['gameState']['mainCardDeck'];
        $columnsCardDeck = $gameData['gameState']['columnsCardDeck'];
        foreach ($mainCardDeck as $card) {
            $this->playingCardsImages[$card->getTypeCard() . $card->getNumCard()] = $this->cardPictureFactory->build($card);
        }
        foreach ($columnsCardDeck as $columnCardDeck) {
            foreach ($columnCardDeck as $card) {
                $this->playingCardsImages[$card->getTypeCard() . $card->getNumCard()] = $this->cardPictureFactory->build($card);
            }
        }
    }

    public function addMessageAboutVictory(array $gameData): void
    {
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'Victory!', 114, 25);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, " /\_/\ \n >^,^<\n  /\ \n (__)__", 114, 20);
    }

    public function updateMainScreen(array $gameData = NULL): void
    {
        $this->clearMainScreen();
        if ($gameData) {
            $this->addCanvasOnMainScreen();
            $this->addCardDecksToMainScreen($gameData);
            $this->addHelperLabelsToTheMainScreen();
            $this->addStepCounterOnMainScreen($gameData);
        }
    }

    public function clearMainScreen(): void
    {
        popen('cls', 'w');       // windows
//        system('clear');  // unix
    }

    public function displayMainScreen(): void
    {
        $picture = '';
        foreach ($this->mainScreen as $lines) {
            $picture .= implode('', $lines);
            $picture .= "\n";
        }
        echo $picture;
    }

    public function addStepCounterOnMainScreen(array $gameData): void
    {
        $stepCounter = 'Number of steps: ' . $gameData['countPlayerMoves'];
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, $stepCounter, 114, 44);
    }

    public function addHelperLabelsToTheMainScreen(): void
    {
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '1', 11, 11);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '2', 25, 11);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '3', 39, 11);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '4', 53, 11);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '5', 67, 11);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '6', 81, 11);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '7', 95, 11);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '1', 25, 1);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '1', 53, 1);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '2', 67, 1);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '3', 81, 1);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '4', 95, 1);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '<---- Section 3', 114, 6);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, '<---- Section 2', 114, 16);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'Main card deck is section 1.', 114, 11);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'Welcome to Kerchief game!', 120, 2);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'H - Get Hint.', 114, 46);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'C - Cancel last move.', 114, 48);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'O - Open a card in the main deck.', 114, 50);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'R - Flip back the main deck of cards.', 114, 52);
    }

    public function addCardDecksToMainScreen(array $gameData): void
    {
        $mainCardDeck = $gameData['gameState']['mainCardDeck'];
        if (empty($mainCardDeck)) {
            $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture(), 6, 2);
        } elseif (end($mainCardDeck)->getCardPosition() === 'open') {
            $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture(), 6, 2);
        } else {
            $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture(end($mainCardDeck)), 6, 2);
        }

        if (empty($mainCardDeck)) {
            $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture(), 6, 2);
        } elseif ($mainCardDeck[0]->getCardPosition() === 'closed') {
            $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture(), 20, 2);
        } else {
            $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture($mainCardDeck[0]), 20, 2);
        }

        $columnsCardDeck = $gameData['gameState']['columnsCardDeck'];
        $columnCounter = 0;
        $initX = 6;
        $initY = 12;

        foreach ($columnsCardDeck as $columnCardDeck) {
            $x = $initX + $columnCounter * self::DISTANCE_BETWEEN_COLUMNS;
            $y = $initY;
            if (empty($columnCardDeck)) {
                $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture(), $x, $y);
                $columnCounter++;
                continue;
            }
            foreach ($columnCardDeck as $card) {
                $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture($card), $x, $y);
                $y += self::DISTANCE_BETWEEN_CARDS_IN_COLUMNS;
            }
            $columnCounter++;
        }

        $winningCardDecks = $gameData['gameState']['winningCardDeck'];
        $columnCounter = 0;
        $initX = 48;
        $initY = 2;
        foreach ($winningCardDecks as $winningCardDeck) {
            $x = $initX + self::DISTANCE_BETWEEN_COLUMNS * $columnCounter;
            $y = $initY;
            if (empty($winningCardDeck)) {
                $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture(), $x, $y);
            } else {
                $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->getCardPicture(end($winningCardDeck)), $x, $y);
            }
            $columnCounter++;
        }
    }

    public function getCardPicture(Card $card = NULL): string
    {
        if (!$card) {
            $objectEmptyCellPicture = new EmptyCell();

            return $objectEmptyCellPicture->getPicture();
        }
        if ($card->getCardPosition() == 'closed') {
            $objectClosedCardDeck = new ClosedCard();

            return $objectClosedCardDeck->getPicture();
        } else {

            return $this->playingCardsImages[$card->getTypeCard() . $card->getNumCard()]->getPicture();
        }
    }

    public function addMoveHelpMessageOnMainScreen(array $moveHelpMessage): void
    {
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'Section from: ' . $moveHelpMessage['sectionFrom'], 114, 25);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'Num Deck From: ' . $moveHelpMessage['numDeckFrom'], 114, 27);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'Section To: ' . $moveHelpMessage['sectionTo'], 114, 29);
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, 'Num Deck To: ' . $moveHelpMessage['numDeckTo'], 114, 31);
    }

    public function addErrorMessageOnMainScreen(array $errorMessage): void
    {
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, $errorMessage[0], 114, 25);
    }

    public function addCanvasOnMainScreen(): void
    {
        $this->mainScreen = $this->painter->addPicture($this->mainScreen, $this->painter->getCanvas()->toString(), 0, 0);
    }
}