<?php
class TryNode extends Node {
	protected $body;
	protected $catch;
	protected $finally;

	public function __construct(Node $body, CatchNode $catch = null, Node $finally = null) {
		$this->body = $body;
		$this->catch = $catch;
		$this->finally = $finally;
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->body = $this->body->visit($ast, $this);

		if ($this->catch) {
			$this->catch = $this->catch->visit($ast, $this);
		}
		if ($this->finally) {
			$this->finally = $this->finally->visit($ast, $this);
		}

		return $this;
	}

	public function gone() {
		$this->body->gone();

		if ($this->catch) {
			$this->catch->gone();
		}

		if ($this->finally) {
			$this->finally->gone();
		}
	}

	public function collectStatistics(AST $ast) {
		$this->body->collectStatistics($ast);
		if ($this->catch) {
			$this->catch->collectStatistics($ast);
		}
		if ($this->finally) {
			$this->finally->collectStatistics($ast);
		}
	}

	public function countLetters(&$letters) {
		$letters['t'] += 1;
		$letters['r'] += 1;
		$letters['y'] += 1;

		if ($this->catch) {
			$this->catch->countLetters($letters);
		}

		if ($this->finally) {
			foreach(array('f', 'i', 'n', 'a', 'l', 'l', 'y') as $l) {
				$letters[$l] += 1;
			}

			$this->finally->countLetters($letters);
		}
	}

	public function toString() {
		return 'try{' . Stream::trimSemicolon($this->body->asBlock()->toString(true, false)) . '}'
			. ($this->catch ? $this->catch->toString() : '')

			. ($this->finally ? 'finally{'
				. Stream::trimSemicolon($this->finally->asBlock()->toString(true, false))
			. '}' : '');
	}
}