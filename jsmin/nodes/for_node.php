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
			$this->initializer = $this->initializer->visit($ast)->optimize();
		}

		if ($this->condition) {
			$this->condition = $this->condition->visit($ast)->looseBoolean();
		}

		if ($this->update) {
			$this->update = $this->update->visit($ast)->optimize();
		}

		$this->body = $this->body->visit($ast)->optimizeBreak();

		return $this;
	}

	public function initializer(Node $n = null) {
		if ($n) {
			if ($this->initializer && !$this->initializer->isVoid()) {
				throw new Exception('Will not overwrite non-void initializer');
			}

			$this->initializer = $n;
		}

		return $this->initializer;
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

	public function gone() {
		if ($this->initializer) {
			$this->initializer->gone();
		}

		if ($this->condition) {
			$this->condition->gone();
		}

		if ($this->update) {
			$this->update->gone();
		}

		$this->body->gone();
	}

	public function last() {
		return count($this->body->asBlock()->nodes) > 1 ? $this : $this->body->last();
	}

	public function toString() {
		return 'for('
			. ($this->initializer && !$this->initializer->isVoid() ? $this->initializer->toString() : '') . ';'
			. ($this->condition   && !$this->condition->isVoid()   ? $this->condition->toString()   : '') . ';'
			. ($this->update      && !$this->update->isVoid()      ? $this->update->toString()      : '') . ')'
				. $this->body->asBlock()->toString(null, true);
	}
}