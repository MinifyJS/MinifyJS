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

		foreach($after as $n) {
			$nodes[] = $n;
		}

		return new ScriptNode($nodes, $this->scope, $this->strict);
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
