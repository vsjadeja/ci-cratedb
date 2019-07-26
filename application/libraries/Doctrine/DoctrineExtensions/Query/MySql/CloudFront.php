<?php

namespace DoctrineExtensions\Query\Mysql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\Lexer;

/**
 * @author Virendra Jadeja <virendrajadeja84@gmail.com>
 */
class CloudFront extends FunctionNode {

    private $string;
    private $cdnEndPoint = 'https://d3lkr5dvw59nx0.cloudfront.net/';

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker) {
        $CI = & get_instance();
        $CI->load->config('cdn');
        $uploadDir = $CI->config->item($this->string->simpleArithmeticExpression->field . '_path');
        return 'CONCAT(\'' . $this->cdnEndPoint . '\',\'' . $uploadDir . '\',' . $sqlWalker->walkArithmeticPrimary($this->string) . ')';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser) {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticExpression();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

}
