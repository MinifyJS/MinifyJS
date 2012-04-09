<?php

class DoWhileNode extends WhileNode {
	public function visit(AST $ast) {
		$this->body = $this->body->visit($ast);
		$this->condition = $this->condition->visit($ast);

		return $this;
	}

	public function toString() {
		return 'do' . Stream::legalStart($this->body->asBlock()->toString(false, true)) . 'while(' . $this->condition->toString() . ')' . ";\0";
	}

	public function last() {
		return $this;
	}
}