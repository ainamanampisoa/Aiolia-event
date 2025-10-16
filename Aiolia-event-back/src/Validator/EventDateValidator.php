<?php

namespace App\Validator;

use App\Entity\Event;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EventDateValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof EventDateConstraint) {
            throw new UnexpectedTypeException($constraint, EventDateConstraint::class);
        }

        if (!$value instanceof Event) {
            return;
        }

        // Vérifier que la date de fin est après la date de début
        if ($value->getStartDate() && $value->getEndDate()) {
            if ($value->getEndDate() <= $value->getStartDate()) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('endDate')
                    ->addViolation();
            }
        }

        // Vérifier que la date de début n'est pas dans le passé (pour les nouveaux événements)
        if ($value->getStartDate() && !$value->getId()) {
            $now = new \DateTime();
            if ($value->getStartDate() < $now) {
                $this->context->buildViolation('La date de début ne peut pas être dans le passé')
                    ->atPath('startDate')
                    ->addViolation();
            }
        }
    }
}

class EventDateConstraint extends Constraint
{
    public string $message = 'La date de fin doit être après la date de début';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

