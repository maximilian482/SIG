<div class="secao">
    <h3>📄 Informações da Loja</h3>

    <div class="info-linha">
        <span class="info-label">Nome:</span>
        <span class="info-valor"><?= campo($loja['nome']) ?></span>
    </div>

    <div class="info-linha">
        <span class="info-label">CNPJ:</span>
        <span class="info-valor"><?= campo($loja['cnpj']) ?></span>
    </div>

    <div class="info-linha">
        <span class="info-label">Inscrição Estadual:</span>
        <span class="info-valor"><?= campo($loja['inscricao_estadual']) ?></span>
    </div>
</div>

<div class="secao">
    <h3>👤 Responsáveis</h3>

    <div class="info-linha">
        <span class="info-label">Gerente:</span>
        <span class="info-valor">
            <?= campo($gerente['nome']) ?>
            <?php if (!empty($gerente['telefone'])): ?>
                — 📞 <?= $gerente['telefone'] ?>
            <?php endif; ?>
        </span>
    </div>

    <div class="info-linha">
        <span class="info-label">Subgerente:</span>
        <span class="info-valor">
            <?= campo($subger['nome']) ?>
            <?php if (!empty($subger['telefone'])): ?>
                — 📞 <?= $subger['telefone'] ?>
            <?php endif; ?>
        </span>
    </div>
</div>

<div class="secao">
    <h3>📍 Endereço</h3>

    <div class="info-linha">
        <span class="info-label">Endereço:</span>
        <span class="info-valor"><?= campo($enderecoCompleto) ?></span>
    </div>
</div>

<div class="secao">
    <h3>📞 Contatos</h3>

    <div class="info-linha">
        <span class="info-label">Telefone fixo:</span>
        <span class="info-valor"><?= campo($loja['telefone_fixo']) ?></span>
    </div>

    <div class="info-linha">
        <span class="info-label">Celular:</span>
        <span class="info-valor"><?= campo($loja['celular']) ?></span>
    </div>

    <div class="info-linha">
        <span class="info-label">Email (Gmail):</span>
        <span class="info-valor"><?= campo($loja['email_gmail']) ?></span>
    </div>

    <div class="info-linha">
        <span class="info-label">Email corporativo:</span>
        <span class="info-valor"><?= campo($loja['email_corporativo']) ?></span>
    </div>
</div>

<div class="secao">
    <h3>🕒 Funcionamento</h3>

    <div class="info-linha">
        <span class="info-label">Horário:</span>
        <span class="info-valor"><?= campo($loja['dias_funcionamento']) ?></span>
    </div>
</div>



