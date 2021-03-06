<?php
class DoWhileNode extends WhileNode {
	public function visit(AST $ast, Node $parent = null) {
		$this->body = $this->body->visit($ast, $this);
		$this->condition = $this->condition->visit($ast, $this);

		$result = $this->condition->asBoolean();

		if ($result === false) {
			return $this->body;
		} elseif ($result === true) {
			return new ForNode(null, null, null, $this->body);
		}

		$this->condition = $this->condition->looseBoolean();

		if ($this->body instanceof Expression && !$this->body->isVoid()) {
			$result = new DoWhileNode(
				new CommaExpression(array_merge($this->body->nodes(), $this->condition->nodes())),
				new VoidExpression(new Number(0))
			);

			return AST::bestOption(array(
				$result->visit($ast, $parent),
				$this
			));
		}

		return $this;
	}

	public function toString() {
		return 'do'
			. Stream::legalStart($this->body->asBlock()->toString(false, true))
		. 'while(' . $this->condition->toString() . ')' . ";\0";
	}

	public function countLetters(&$letters) {
		foreach(array('d', 'o', 'w', 'h', 'i', 'l', 'e') as $l) {
			$letters[$l] += 1;
		}

		$this->body->countLetters($letters);
		$this->condition->countLetters($letters);
	}

	public function last() {
		return $this;
	}
}