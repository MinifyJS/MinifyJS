<?php
class ScriptNode extends BlockStatement {
	public function __construct(array $nodes, Scope $scope, $strict = false) {
		$this->nodes = $nodes;
		$this->scope = $scope;
		$this->strict = $strict;

		parent::__construct($nodes);
	}


	public function visit(AST $ast) {
		$new = parent::visit($ast);

		$nodes = array();
		$after = array();

		foreach($new->nodes() as $n) {
			if ($n instanceof FunctionNode) {
				$nodes[] = $n;
			} else {
				$after[] = $n;
			}
		}

		foreach(array_slice($after, 0, -1) as $n) {
			$nodes[] = $n;
		}

		if (($last = end($after)) && (!($last instanceof ReturnNode) || !$last->value()->isVoid())) {
			$nodes[] = $last;
		}

		$revisit = false;
		$result = $this->reverseIfElse($nodes, $revisit);

		if ($revisit) {
			return $result->visit($ast);
		}

		return $result;
	}

	protected function reverseIfElse(array $nodes, &$revisit) {
		// we will loop, if we find if(...) return; ... , transform into if(!...) { ... }
		// ^ note that this optimisation is only valid in ScriptStatements
		$add = $base = new ScriptNode(array(), $this->scope, $this->strict);
		$last = count($nodes) - 1;

		foreach($nodes as $i => $node) {
			if ($node instanceof IfNode && !$node->_else() && $node->then() instanceof ReturnNode && $node->then()->value()->isVoid()) {
				// got one!
				$old = $add;
				$add = new BlockStatement(array());

				$old->add(new IfNode($node->condition()->negate(), $add));

				$revisit = true;
			} else {
				$add->add($node);
			}
		}

		return $base;
	}

	public function rootElement() {
		if (count($this->nodes) !== 1 || !($this->nodes[0] instanceof Expression)) {
			throw new Exception('Invalid define');
		}

		return $this->nodes[0];
	}

	public function collectStatistics(AST $ast) {
		$ast->visitScope($this->scope);
		parent::collectStatistics($ast);
		$ast->visitScope($this->scope->parent());
	}

	public function toString($noBraces = true, $forceOut = false) {
		return Stream::trimSemicolon(parent::toString($noBraces, $forceOut));
	}
}
