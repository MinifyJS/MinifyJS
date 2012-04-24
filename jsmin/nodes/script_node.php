<?php
class ScriptNode extends BlockStatement {
	public function __construct(array $nodes, Scope $scope, $strict = false) {
		$this->nodes = $nodes;
		$this->scope = $scope;
		$this->strict = $strict;

		parent::__construct($nodes);
	}


	public function visit(AST $ast) {
		$ast->visitScope($this->scope);
		$new = parent::visit($ast);

		$nodes = array();
		$after = array();

		foreach($new->nodes as $n) {
			if ($n instanceof FunctionNode && !$n instanceof FunctionExpression) {
				$nodes[] = $n;
			} else {
				$after[] = $n;
			}
		}

		$new->nodes = array_merge($nodes, $after);

		$ast->visitScope($this->scope->parent());

		return $new;
	}

	public function toString($noBraces = true, $forceOut = false) {
		return Stream::trimSemicolon(parent::toString($noBraces, $forceOut));
	}
}
