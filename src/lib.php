<?php

declare(strict_types=1);

namespace SOFe\PmEvent;

use Closure;
use pocketmine\block\Block;
use pocketmine\event\Event;
use pocketmine\plugin\Plugin;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Traverser;

final class PmEvent {
	/**
	 * @template E of Event
	 * @param class-string<E> $event
	 * @param Closure(E): string $interpreter
	 * @return Traverser<E>
	 */
	public static function watch(Plugin $plugin, string $event, string $key, Closure $interpreter) : Traverser {
		/** @phpstan-ignore-next-line */ // phpstan cannot identify the call correctly as `EventMux<E>::watch()`
		return EventMux::watch($plugin, $event, $key, $interpreter);
	}

	/**
	 * @return Traverser<Block>
	 */
	public static function watchBlock(Position $position) : Traverser {
		return BlockWatch::watch($position);
	}
}
