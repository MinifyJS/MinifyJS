<?php
class WhileNode extends Node {
	protected $condition;

	protected $body;

	public function __construct(Expression $cond, Node $body) {
		$this->condition = $cond;
		$this->body = $body;
	}

	public function visit(AST $ast) {
		$this->condition = $this->condition->visit($ast)->looseBoolean();
		$this->body = $this->body->visit($ast)->optimizeBreak();

		$result = $this->condition->asBoolean();

		if ($result === false) {
			return new VoidExpression(new Number(0));
		} elseif ($result === true) {
			return new ForNode(null, null, null, $this->body);
		}

		return $this;
	}

	public function gone() {
		$this->condition->gone();
		$this->body->gone();
	}

	public function collectStatistics(AST $ast) {
		$this->condition->collectStatistics($ast);
		$this->body->collectStatistics($ast);
	}

	public function last() {
		return count($this->body->asBlock()->nodes) > 1 ? $this : $this->body->last();
	}


	public function countLetters(&$letters) {
		foreach(array('w', 'h', 'i', 'l', 'e') as $l) {
			$letters[$l] += 1;
		}

		$this->condition->countLetters($letters);
		$this->body->countLetters($letters);
	}

	public function toString() {
		return 'while(' . $this->condition->toString() . ')' . $this->body->asBlock()->toString(null, true);
	}
}