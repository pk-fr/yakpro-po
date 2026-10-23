<?php
// min-php: 8.1
// enum case names are scrambled like class constants: ->name returns the scrambled name (documented limitation)
enum Suit: string {
    case Hearts = 'H';
    case Spades = 'S';
    const Wild = self::Spades;
    public function color(): string { return match ($this) { Suit::Hearts => 'Red', Suit::Spades => 'Black' }; }
}
enum Status { case Active; case Inactive; }
echo Suit::Hearts->color(), Suit::from('S')->name === Suit::Spades->name ? 'same' : 'diff', Suit::tryFrom('X') === null ? 'null' : 'x', Suit::Wild->value, count(Suit::cases()), "\n";
echo Status::Active === Status::cases()[0] ? 'first' : 'x', Status::Active instanceof UnitEnum ? 'y' : 'n', "\n";
