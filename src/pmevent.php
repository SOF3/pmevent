<?php

declare(strict_types=1);

namespace SOFe\PmEvent;

use Closure;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use SOFe\AwaitGenerator\Channel;
use SOFe\AwaitGenerator\Traverser;

final class PmEvent {
	/** @var array<class-string<Event>, Mux<Event>> */
	private static $muxStore = [];

	/**
	 * @template E of Event
	 * @param class-string<E> $event
	 * @param Closure(E): string $interpreter
	 * @return Traverser<E>
	 */
	public static function watch(Plugin $plugin, string $event, string $key, Closure $interpreter) : Traverser {
		if(!isset(self::$muxStore[$event])) {
			$mux = new Mux($event, $interpreter);
			$mux->init($plugin);
			self::$muxStore[$event] = $mux;
		}

		/** @var Mux<E> $mux */
		$mux = self::$muxStore[$event];
		return $mux->subscribe($key);
	}
}

/**
 * @template E of Event
 * @internal
 */
final class Mux {
	/** @var array<string, Channel<E>[]> */
	public array $index = [];

	/**
	 * @param class-string<E> $event
	 * @param Closure(E): string $interpreter
	 */
	public function __construct(
		private string $event,
		private Closure $interpreter,
	) {
	}

	public function init(Plugin $plugin) : void {
		Server::getInstance()->getPluginManager()->registerEvent($this->event, $this->handle(...), EventPriority::MONITOR, $plugin);
	}

	/**
	 * @param E $event
	 */
	public function handle($event) : void {
		$key = ($this->interpreter)($event);
		foreach($this->index[$key] ?? [] as $channel) {
			$channel->sendWithoutWait($event);
		}
	}

	/**
	 * @return Traverser<E>
	 */
	public function subscribe(string $key) : Traverser {
		$channel = new Channel;

		$this->index[$key][spl_object_id($channel)] = $channel;

		return Traverser::fromClosure(function() use($channel, $key) {
			try {
				while(true) {
					$event = yield from $channel->receive();
					yield $event => Traverser::VALUE;
				}
			} finally {
				unset($this->index[$key][spl_object_id($channel)]);
			}
		});
	}
}
