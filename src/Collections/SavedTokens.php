<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Pantheon\Terminus\Models\SavedToken;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class SavedTokens
 * @package Pantheon\Terminus\Collections
 */
class SavedTokens extends TerminusCollection implements ConfigAwareInterface, DataStoreAwareInterface
{
    use ConfigAwareTrait;
    use DataStoreAwareTrait;

    const PRETTY_NAME = 'tokens';
    /**
     * @var string
     */
    protected $collected_class = SavedToken::class;

    /**
     * Adds a model to this collection
     *
     * @param object $model_data Data to feed into attributes of new model
     * @param array $options Data to make properties of the new model
     * @return TerminusModel
     */
    public function add($model_data, array $options = [])
    {
        $model = parent::add($model_data, $options);
        $model->setDataStore($this->getDataStore());
        return $model;
    }

    /**
     * Saves a machine token to the tokens directory and logs the user in
     *
     * @param string $token The machine token to be saved
     */
    public function create($token_string)
    {
        var_dump(['tks' => $token_string]);

        $token_nickname = "token-" . \uniqid();
        $this->getContainer()->add($token_nickname, SavedToken::class)
            ->addArguments([
                (object)['token' => $token_string],
                ['collection' => $this]
            ]);
        /** @var \Pantheon\Terminus\Models\SavedToken $token */
        $token = $this->getContainer()->get($token_nickname);
        $token->setDataStore($this->getDataStore());
        $user = $token->logIn();
        $user->fetch();
        $user_email = $user->get('email');
        var_dump($user_email);
        $token->id = $user_email;
        $token->set('email', $user_email);
        var_dump(['token' => $token->serialize()]);
        $token->saveToDir();
        $this->models[$token->id] = $token;
    }

    /**
     * Delete all of the saved tokens.
     */
    public function deleteAll()
    {
        foreach ($this->all() as $token) {
            $token->delete();
        }
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if (empty(parent::getData())) {
            $keys = $this->getDataStore()->keys();

            $tokens = [];
            foreach ($keys as $key) {
                if ('tokens' !== $key) {
                    continue;
                }

                $token = $this->getDataStore()->get($key);
                if (null === $token) {
                    continue;
                }

                $tokens[] = $this->getDataStore()->get($key);
            }

            if (count($tokens) > 0) {
                $this->setData($tokens);
            }
        }
        return parent::getData();
    }
}
