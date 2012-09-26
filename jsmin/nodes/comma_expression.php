<?php
class CommaExpression extends Expression {
	public function __construct(array $entries) {
		$this->nodes = $entries;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$nodes = array();
		$last = count($this->nodes) - 1;
		$prev = null;

		foreach($this->nodes as $i => $e) {
			foreach($e->visit($ast, $this)->nodes() as $n) {
				if ($i !== $last) {
					$n = $n->optimize();

					if ($n->isConstant()) {
						$n->gone();
						continue;
					}
				}

				if ($prev && ($n instanceof BinaryExpression || $n instanceof InExpression || $n instanceof InstanceofExpression)) {
					if (($r = $prev->represents()) instanceof IdentifierExpression) {
						$q = $n->left();
						$r = $r->value();

						if ($q instanceof IdentifierExpression && $r === $q->value()) {
							$n->left(array_pop($nodes));
						} elseif (!$q->hasSideEffects()) {
							$q = $n->right();
							if ($q instanceof IdentifierExpression && $r === $q->value()) {
								$n->right(array_pop($nodes));
							}
						}
					}
				}

				$nodes[] = $prev = $n;
			}
		}

		if (!$nodes) {
			return new VoidExpression(new Number(0));
		}

		$size = count($nodes);
		if ($size > 1 && $nodes[$size - 1] instanceof IdentifierExpression
				&& $nodes[$size - 2]->represents()->value() === $nodes[$size - 1]->value()) {
			array_splice($nodes, -1);
			--$size;
		}

		if ($size === 1) {
			return $nodes[0];
		}

		return new CommaExpression($nodes);
	}

	public function optimize() {
		$result = array();
		foreach($this->nodes as $n) {
			$x = $n->optimize();

			if (!$x->isConstant()) {
				$result[] = $n;
			}
		}

		switch (count($result)) {
		case 0:
			return new VoidExpression(new Number(0));
		case 1:
			return $result[0];
		default:
			return new CommaExpression($result);
		}
	}

	public function first() {
		if ($this->nodes) {
			return $this->nodes[0]->first();
		}
	}

	public function gone() {
		foreach($this->nodes as $n) {
			$n->gone();
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

	public function toString($indent = true) {
		$result = implode(',' . (AST::$options['beautify'] ? "\n" : ''), $this->nodes);
		if (AST::$options['beautify'] && $indent) {
			$result = ltrim(Stream::indent($result));
		}
		return $result;
	}

	public function asBoolean() {
		if (!$this->isConstant()) {
			return null;
		}

		return $this->represents()->asBoolean();
	}

	public function asString() {
		if (!$this->hasSideEffects()) {
			return $this->represents()->asString();
		}
	}

	public function asNumber() {
		if (!$this->hasSideEffects()) {
			return $this->represents()->asNumber();
		}
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
			$that->nodes[count($that->nodes) - 1] = $that->represents()->negate();
		}

		return AST::bestOption(array(
			new NotExpression($this),
			$that
		));
	}

	public function countLetters(&$letters) {
		foreach ($this->nodes as $n) {
			$n->countLetters($letters);
		}
	}

	public function precedence() {
		return 1;
	}
}
