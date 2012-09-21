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

	public function visit(AST $ast, Node $parent = null) {
		if ($this->initializer) {
			$this->initializer = $this->initializer->visit($ast, $this)->optimize();
		}

		if ($this->condition) {
			$this->condition = $this->condition->visit($ast, $this)->looseBoolean();
		}

		if ($this->update) {
			$this->update = $this->update->visit($ast, $this)->optimize();
		}

		$this->body = $this->body->visit($ast, $this)->optimizeBreak();

		if ($this->body instanceof Expression && !$this->update) {
			return AST::bestOption(array(
				new ForNode($this->initializer, $this->condition, $this->body, VoidExpression::nil()),
				$this
			));
		}

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

	public function moveExpression(Expression $x) {
		if (!$this->initializer || $this->initializer->isVoid()) {
			$this->initializer = $x;
			return true;
		}

		if ($this->initializer instanceof Expression) {
			$this->initializer = new CommaExpression(array_merge($x->nodes(), $this->initializer->nodes()));
			return true;
		}

		return false;
	}

	public function last() {
		return count($this->body->asBlock()->nodes) > 1 ? $this : $this->body->last();
	}


	public function countLetters(&$letters) {
		$letters['f'] += 1;
		$letters['o'] += 1;
		$letters['r'] += 1;

		if ($this->initializer) {
			$this->initializer->countLetters($letters);
		}

		if ($this->condition) {
			$this->condition->countLetters($letters);
		}

		if ($this->update) {
			$this->update->countLetters($letters);
		}

		$this->body->countLetters($letters);
	}


	public function toString() {
		$space = AST::$options['beautify'] ? ' ' : '';
		return 'for('
			. ($this->initializer && !$this->initializer->isVoid() ? $this->initializer->toString(true) : $space) . ';'
			. ($this->condition   && !$this->condition->isVoid()   ? $this->condition->toString()       : $space) . ';'
			. ($this->update      && !$this->update->isVoid()      ? $this->update->toString()          : $space) . ')'
				. $this->body->asBlock()->toString(null, true);
	}
}
