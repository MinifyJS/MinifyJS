<?php
class ScriptNode extends BlockStatement {
	public function visit(AST $ast) {
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

		return $new;
	}

	public function toString($noBraces = true, $forceOut = false) {
		return Stream::trimSemicolon(parent::toString($noBraces, $forceOut));
	}
}
