<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Worker;

use SchemaKeeper\Provider\ProviderFactory;
use SchemaKeeper\Tests\SchemaTestCase;
use SchemaKeeper\Worker\Saver;
use SchemaKeeper\Worker\Verifier;

class VerifierTest extends SchemaTestCase
{
    /**
     * @var Verifier
     */
    private $target;

    /**
     * @var Saver
     */
    private $saver;

    public function setUp()
    {
        parent::setUp();

        $conn = $this->getConn();
        $params = $this->getDbParams();
        $providerFactory = new ProviderFactory();
        $provider = $providerFactory->createProvider($conn, $params);

        $this->target = new Verifier($provider);
        $this->saver = new Saver($provider);

        exec('rm -rf /tmp/schema_keeper');
    }

    public function testOk()
    {
        $this->saver->save('/tmp/schema_keeper');
        $this->target->verify('/tmp/schema_keeper');
    }

    /**
     * @expectedException \SchemaKeeper\Exception\NotEquals
     * @expectedExceptionMessage Dump and current database not equals {"expected":[],"actual":{"triggers":{"public.test_table.test_trigger":"CREATE TRIGGER test_trigger BEFORE UPDATE ON test_table FOR EACH ROW EXECUTE PROCEDURE trig_test()"}}}
     */
    public function testDiff()
    {
        $this->saver->save('/tmp/schema_keeper');
        exec('rm -r /tmp/schema_keeper/structure/public/triggers');

        $this->target->verify('/tmp/schema_keeper');
    }
}
