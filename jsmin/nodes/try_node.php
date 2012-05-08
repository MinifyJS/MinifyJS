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

	public function visit(AST $ast) {
		$this->body = $this->body->visit($ast);

		if ($this->catch) {
			$this->catch = $this->catch->visit($ast);
		}
		if ($this->finally) {
			$this->finally = $this->finally->visit($ast);
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

	public function toString() {
		return 'try{' . Stream::trimSemicolon($this->body->asBlock()->toString(true)) . '}'
			. ($this->catch ? $this->catch->toString() : '')

			. ($this->finally ? 'finally{'
				. Stream::trimSemicolon($this->finally->asBlock()->toString(true))
			. '}' : '');
	}
}