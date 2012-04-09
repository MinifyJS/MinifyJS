<?php
class ForNode extends Node {
	protected $initializer;
	protected $condition;
	protected $update;

	protected $body;

	public function __construct(
		Node $init = null,
		Expression $cond = null,
		Expression $up = null,
		Node $body
	) {
		$this->initializer = $init;
		$this->condition = $cond;
		$this->update = $up;

		$this->body = $body;

		parent::__construct();
	}

	public function visit(AST $ast) {
		if ($this->initializer) {
			$this->initializer = $this->initializer->visit($ast);
		}

		if ($this->condition) {
			$this->condition = $this->condition->visit($ast);
		}

		if ($this->update) {
			$this->update = $this->update->visit($ast);
		}

		$this->body = $this->body->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		if ($this->initializer) {
			$this->initializer->collectStatistics($ast);
		}

		if ($this->condition) {
			$this->condition->collectStatistics($ast);
		}

		if ($this->update) {
			$this->update->collectStatistics($ast);
		}

		$this->body->collectStatistics($ast);
	}

	public function toString() {
		return 'for('
			. ($this->initializer && !$this->initializer->isVoid() ? $this->initializer->toString() : '') . ';'
			. ($this->condition   && !$this->condition->isVoid()   ? $this->condition->toString()   : '') . ';'
			. ($this->update      && !$this->update->isVoid()      ? $this->update->toString()      : '') . ')'
				. $this->body->asBlock()->toString(null, true);
	}
}