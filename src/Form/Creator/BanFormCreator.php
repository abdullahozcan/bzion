<?php
/**
 * This file contains a form creator for Bans
 *
 * @license    https://github.com/allejo/bzion/blob/master/LICENSE.md GNU General Public License Version 3
 */

namespace BZIon\Form\Creator;

use BZIon\Form\Type\IpType;
use BZIon\Form\Type\PlayerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Form creator for bans
 */
class BanFormCreator extends ModelFormCreator
{
    /**
     * {@inheritDoc}
     */
    protected function build($builder)
    {
        return $builder
            ->add('player', new PlayerType(), array(
                'disabled' => $this->isEdit(),
            ))
            ->add(
                $builder->create('automatic_expiration', 'checkbox', array(
                    'data' => true,
                    'required' => false,
                ))->setDataLocked(false) // Don't lock the data so we can change
                                         // the default value later if needed
            )
            ->add(
                $builder->create('expiration', 'datetime', array(
                    'data' => \TimeDate::now(),
                ))->setDataLocked(false)
            )
            ->add('reason', 'text', array(
                'constraints' => new NotBlank(),
            ))
            ->add('server_join_allowed', 'checkbox', array(
                'data' => true,
                'required' => false,
            ))
            ->add('server_message', 'text', array(
                'required' => false,
                'constraints' => new Length(array(
                    'max' => 150,
                ))
            ))
            ->add('ip_addresses', new IpType(), array(
                'required' => false,
            ))
            ->add('enter', 'submit')
            ->setDataLocked(false);
    }

    /**
     * {@inheritDoc}
     */
    public function fill($form, $ban)
    {
        $form->get('player')->get('players')->setData($ban->getVictim());
        $form->get('reason')->setData($ban->getReason());
        $form->get('server_message')->setData($ban->getServerMessage());
        $form->get('server_join_allowed')->setData($ban->allowedServerJoin());
        $form->get('ip_addresses')->setData($ban->getIpAddresses());

        if ($ban->willExpire()) {
            $form->get('expiration')->setData($ban->getExpiration());
        } else {
            $form->get('automatic_expiration')->setData(false);
        }
    }
}