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
		$result = $this->reverseIfElse(
			$nodes,
			new ScriptNode(array(), $this->scope, $this->strict),
			$revisit
		);

		if ($revisit) {
			return $result->visit($ast);
		}

		return $result;
	}

	protected function reverseIfElse(array $nodes, BlockStatement $base, &$revisit) {
		// we will loop, if we find if(...) return; ... , transform into if(!...) { ... }
		// ^ note that this optimisation is only valid in ScriptStatements
		$add = $base;
		$last = count($nodes) - 1;

		foreach($nodes as $i => $node) {
			if ($node instanceof IfNode && $node->then() instanceof ReturnNode && $node->then()->value()->isVoid()) {
				// got one!
				$old = $add;
				$add = new BlockStatement(array());

				if ($node->_else() && !$node->_else()->isVoid()) {
					$add = $this->reverseIfElse($node->_else()->nodes(), $add, $revisit);
					$revisit = true;
				}

				$old->add(new IfNode($node->condition()->negate()->looseBoolean(), $add));

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
		//while (($last = end($this->nodes)) instanceof ReturnNode && $last->value()->isVoid()) {
		//	array_splice($this->nodes, -1);
		//}

		return Stream::trimSemicolon(parent::toString($noBraces, $forceOut));
	}
}
