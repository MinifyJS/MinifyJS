<?php
class DoWhileNode extends WhileNode {
	public function visit(AST $ast) {
		$this->body = $this->body->visit($ast);
		$this->condition = $this->condition->visit($ast);

		$result = $this->condition->asBoolean();

		if ($result === false) {
			return $this->body;
		} elseif ($result === true) {
			return new ForNode(null, null, null, $this->body);
		}

		return $this;
	}

	public function toString() {
		return 'do' . Stream::legalStart($this->body->asBlock()->toString(false, true)) . 'while(' . $this->condition->toString() . ')' . ";\0";
	}

	public function last() {
		return $this;
	}
}