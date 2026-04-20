<?php

namespace App\Enums;

enum UserType: string
{
    case GESTAO = 'gestao';
    case PASSAGEIRO = 'passageiro';
    case MOTORISTA = 'motorista';
}
