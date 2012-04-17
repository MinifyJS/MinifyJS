<?php
class CommaExpression extends Expression {
	public function __construct(array $entries) {
		$this->nodes = $entries;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$nodes = array();

		foreach($this->nodes as $i => $e) {
			$node = $e->visit($ast);

			if ($node && !$node->isVoid()) {
				if ($node instanceof CommaExpression) {
					foreach($node->nodes as $n) {
						$n = $n->removeUseless();

						if (!$n->isVoid()) {
							$nodes[] = $n;
						}
					}
				} else {
					$n = $node->removeUseless();

					if (!$n->isVoid()) {
						$nodes[] = $n->removeUseless();
					}
				}
			}
		}

		if (!$nodes) {
			return new VoidExpression(new Number(0));
		}

		return new CommaExpression($nodes);
	}

	public function first() {
		if ($this->nodes) {
			return $this->nodes[0];
		}
	}

	public function nodes() {
		return $this->nodes;
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $e) {
			$e->collectStatistics($ast);
		}
	}

	public function toString() {
		return implode(',', $this->nodes);
	}

	public function asBoolean() {
		if (!$this->isConstant()) {
			return null;
		}

		return $this->represents()->asBoolean();
	}

	public function asString() {
		if (!$this->isConstant()) {
			return null;
		}

		return $this->represents()->asString();
	}

	public function asNumber() {
		if (!$this->isConstant()) {
			return null;
		}

		return $this->represents()->asNumber();
	}

	public function type() {
		return $this->represents()->type();
	}

	public function represents() {
		return end($this->nodes);
	}

	public function isConstant() {
		foreach($this->nodes as $n) {
			if (!$n->isConstant()) {
				return false;
			}
		}

		return true;
	}

	public function negate() {
		$that = clone $this;

		if ($that->nodes) {
			$that->nodes[count($that->nodes) - 1] = $that->represents->negate();
		}

		return AST::bestOption(array(
			new NotExpression($this),
			$that
		));
	}

	public function precedence() {
		return 1;
	}
}
