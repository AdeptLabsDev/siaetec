# Contexto do Projeto — Sistema de Intenção Alimentar Escolar

## 1. Proposta do sistema

O projeto tem como objetivo principal auxiliar escolas na redução do desperdício alimentar por meio de um sistema de registro de intenção alimentar dos alunos.

A proposta consiste em permitir que cada aluno informe, antes de um horário limite definido pela administração, se pretende ou não consumir a refeição escolar de determinado dia. Com base nessas respostas, o sistema poderá consolidar os dados e gerar uma estimativa de demanda para a cozinha ou equipe responsável pela merenda.

O sistema não tem como objetivo prever com precisão absoluta a quantidade de alunos que irão se alimentar, pois podem ocorrer faltas, mudanças de decisão ou alunos que não responderam à enquete. A proposta é fornecer dados prévios para apoiar a tomada de decisão da escola, reduzindo a margem de erro no preparo das refeições e contribuindo para um planejamento alimentar mais eficiente.

## 2. Problema identificado

Atualmente, a produção de refeições escolares costuma ser baseada em estimativas gerais, histórico de presença ou quantidade total de alunos matriculados. No entanto, nem todos os alunos consomem a merenda todos os dias.

Alguns fatores que podem causar diferença entre a quantidade produzida e a quantidade consumida são:

- ausência de alunos;
- alunos que trazem alimento de casa;
- baixa aceitação do cardápio do dia;
- mudanças na rotina escolar;
- falta de uma previsão diária mais próxima da realidade.

Essa diferença pode gerar excesso de alimentos preparados, sobras e desperdício. O sistema busca atuar justamente antes da produção da refeição, oferecendo uma estimativa mais informada à gestão escolar.

## 3. Objetivo geral

Desenvolver um sistema web com backend em PHP e banco de dados MySQL para registrar a intenção alimentar dos alunos e auxiliar a escola no planejamento da quantidade de refeições a serem preparadas diariamente.

## 4. Objetivos específicos

- Permitir que alunos acessem o sistema utilizando seu RM e senha.
- Permitir que alunos informem se pretendem consumir a refeição cadastrada para o dia.
- Permitir que administradores cadastrem alunos, turmas e refeições.
- Permitir que administradores acompanhem os resultados consolidados das respostas.
- Gerar uma estimativa de quantidade de refeições com base nas intenções registradas.
- Possibilitar o envio ou compartilhamento do resumo da enquete com a equipe responsável pela merenda.
- Armazenar os dados de forma organizada para permitir análise histórica e expansão futura do sistema.

## 5. Escopo do MVP

O MVP será composto por dois tipos de usuários:

1. Aluno
2. Admin

O perfil Admin representa a coordenação, direção ou gestores responsáveis por cadastrar informações e analisar os dados. Os funcionários ou cozinheiros não terão acesso direto ao sistema neste primeiro momento, pois foi identificado que eles preferem receber o resultado da enquete de forma prática, possivelmente via WhatsApp.

Dessa forma, a cozinha será considerada uma destinatária das informações, e não um tipo de usuário dentro do sistema.

## 6. Perfis de usuário

### 6.1 Aluno

O aluno será o usuário responsável por registrar a intenção alimentar.

Principais permissões do aluno:

- acessar o sistema com RM e senha;
- visualizar a refeição disponível para resposta;
- responder se pretende ou não consumir a refeição;
- alterar sua resposta enquanto a enquete estiver aberta;
- visualizar a confirmação de sua resposta.

O RM será utilizado como identificador escolar do aluno, pois é um dado já fornecido pela instituição no momento do cadastro do estudante.

### 6.2 Admin

O Admin será o usuário responsável pela gestão do sistema.

Principais permissões do Admin:

- cadastrar alunos;
- cadastrar turmas;
- cadastrar refeições;
- abrir e fechar enquetes;
- visualizar os resultados das intenções alimentares;
- analisar dados por refeição, data e turma;
- gerar o resumo que poderá ser encaminhado à equipe da cozinha;
- gerenciar usuários ativos e inativos.

O Admin não representa apenas uma pessoa específica, mas sim o perfil administrativo do sistema, podendo ser utilizado por coordenação, direção ou gestores autorizados.

## 7. Regras de negócio principais

### 7.1 Login do aluno

O aluno deverá acessar o sistema utilizando RM e senha.

O RM deve ser único dentro do sistema, pois identifica individualmente cada aluno.

Regras relacionadas:

- um RM não pode pertencer a mais de um aluno;
- o aluno não deve alterar seu próprio RM;
- apenas o Admin pode cadastrar ou alterar dados escolares do aluno;
- alunos inativos não devem conseguir acessar o sistema;
- o sistema deve diferenciar usuários do tipo Aluno e Admin.

### 7.2 Cadastro prévio de alunos

Para evitar cadastros falsos, os alunos são previamente cadastrados pelo Admin.

O fluxo esperado é:

1. Admin cadastra o aluno no sistema com nome, RM e turma.
2. O sistema atribui automaticamente uma senha padrão ao aluno (igual ao próprio RM).
3. O aluno acessa o sistema pela primeira vez com RM e senha padrão.
4. O sistema detecta que é o primeiro acesso e redireciona obrigatoriamente para a tela de criação de senha.
5. O aluno define sua própria senha antes de acessar qualquer outra funcionalidade.
6. O aluno passa a responder às enquetes disponíveis.

Esse modelo mantém o controle institucional, reduz o risco de usuários não autorizados e garante que cada aluno possua uma senha pessoal desde o primeiro uso.

### 7.3 Cadastro de refeições

O Admin deverá cadastrar a refeição disponível para determinada data.

Cada refeição poderá conter informações como:

- data da refeição;
- título ou tipo da refeição;
- descrição do cardápio;
- horário limite para resposta;
- status da enquete.

O horário limite é importante para que as respostas sejam recebidas antes do preparo da comida.

### 7.4 Registro de intenção alimentar

Cada aluno poderá registrar sua intenção em relação a uma refeição específica.

As respostas previstas para o MVP são:

- Sim: pretende consumir a refeição.
- Não: não pretende consumir a refeição.

Para manter o MVP objetivo, a opção “talvez” não será priorizada inicialmente, pois a cozinha precisa de uma estimativa mais direta para tomada de decisão.

Regras relacionadas:

- um aluno só pode possuir uma resposta por refeição;
- o aluno pode alterar sua resposta apenas enquanto a enquete estiver aberta;
- após o horário limite, a resposta não poderá mais ser criada ou alterada;
- a resposta deve estar relacionada obrigatoriamente a um aluno e a uma refeição.

### 7.5 Fechamento da enquete

A enquete é encerrada automaticamente quando o horário limite definido pelo Admin é atingido. Não há necessidade de ação manual para fechar a enquete.

O mecanismo de fechamento funciona por verificação reativa: a cada requisição, o sistema compara o horário atual com o deadline da refeição. Se o horário limite já passou, a enquete é tratada como encerrada, bloqueando novas respostas e alterações. Isso é implementado sem necessidade de processos em background ou agendamento externo (cron job).

Quando a enquete estiver fechada:

- alunos não podem mais responder;
- alunos não podem mais alterar respostas;
- o Admin pode visualizar o resultado consolidado;
- o sistema pode gerar um resumo para envio à cozinha.

### 7.6 Envio do resultado para a cozinha

No MVP, a equipe da cozinha não terá login próprio.

O sistema deverá gerar um resumo com os principais dados da enquete para que o Admin possa encaminhar à cozinha.

O envio do resumo à cozinha será realizado via WhatsApp, com integração direta à Evolution API. A Evolution API conecta-se ao WhatsApp via pareamento por QR Code, sem necessidade de aprovação de conta Business junto à Meta.

A arquitetura de integração prevista é:

- Evolution API hospedada no Railway (serviço de hospedagem em nuvem);
- sistema PHP realiza requisições HTTP à Evolution API após a geração do resumo;
- o Admin aciona o envio pela interface do sistema, que transmite o resumo ao número ou grupo da cozinha configurado.

Essa abordagem mantém o envio integrado ao fluxo do sistema, sem exigir ação manual fora da plataforma.

Informações presentes no resumo do MVP:

- data da refeição;
- cardápio;
- total de alunos cadastrados;
- total de alunos que responderam;
- quantidade de alunos que responderam "Sim";
- quantidade de alunos que responderam "Não";
- quantidade de alunos que não responderam.

A sugestão de quantidade de refeições a preparar e o cálculo de margem de segurança não fazem parte do MVP. Esses recursos poderão ser incorporados em versões futuras, após validação do uso real do sistema.

### 7.7 Gestão de ano letivo e turmas

As turmas são vinculadas a um ano letivo específico. Quando o ano letivo muda, o Admin deve inativar as turmas antigas e criar as novas turmas para o período vigente.

Regras relacionadas:

- turmas inativas não aparecem como opção ao cadastrar novos alunos;
- alunos vinculados a turmas inativas permanecem no sistema com seus históricos preservados;
- o Admin pode atualizar o vínculo de um aluno para uma nova turma quando necessário, como em casos de transferência ou progressão de série;
- as intenções alimentares registradas são preservadas com referência à turma vigente no momento da resposta, garantindo integridade do histórico.

Esse modelo evita a necessidade de migração de dados entre anos letivos e mantém o histórico completo de participação.

## 8. Entidades principais do backend

Para manter o sistema organizado e escalável, são previstas as seguintes entidades principais no banco de dados:

1. Usuários
2. Alunos
3. Turmas
4. Refeições
5. Intenções alimentares
6. Resumos ou registros de envio

## 9. Descrição das entidades

### 9.1 Usuários

Entidade responsável pelos dados de autenticação e controle de acesso.

Campos conceituais:

- identificador do usuário;
- nome;
- senha;
- tipo de usuário;
- status;
- data de criação.

Tipos de usuário previstos:

- aluno;
- admin.

Essa entidade permite que o sistema controle permissões e diferencie as ações disponíveis para alunos e administradores.

### 9.2 Alunos

Entidade responsável pelos dados escolares do aluno.

Campos conceituais:

- identificador do aluno;
- vínculo com o usuário;
- RM;
- turma;
- ano letivo;
- status escolar.

A separação entre Usuários e Alunos é recomendada porque nem todo usuário do sistema é necessariamente um aluno. O Admin, por exemplo, precisa acessar o sistema, mas não possui RM de aluno.

### 9.3 Turmas

Entidade responsável por organizar os alunos em grupos escolares.

Campos conceituais:

- identificador da turma;
- nome da turma;
- curso;
- período;
- ano letivo;
- status.

A tabela de turmas permite análises mais detalhadas, como quantidade de respostas por turma e comparação de adesão entre diferentes grupos.

### 9.4 Refeições

Entidade responsável pelo cadastro das refeições ou enquetes alimentares.

Campos conceituais:

- identificador da refeição;
- data da refeição;
- título;
- descrição do cardápio;
- horário limite de resposta;
- status da enquete;
- usuário Admin responsável pelo cadastro.

Cada refeição funciona como uma oportunidade de resposta para os alunos.

### 9.5 Intenções alimentares

Entidade responsável por registrar a resposta de cada aluno em relação a uma refeição.

Campos conceituais:

- identificador da intenção;
- aluno relacionado;
- refeição relacionada;
- resposta;
- data e horário da resposta;
- data e horário da última alteração, se houver.

Essa é uma das entidades centrais do sistema, pois permite calcular a demanda prevista para cada refeição.

### 9.6 Resumos ou registros de envio

Entidade responsável por registrar os resumos gerados e enviados à cozinha via WhatsApp. Faz parte do MVP.

Campos conceituais:

- identificador do registro;
- refeição relacionada;
- mensagem gerada;
- destinatário ou grupo de destino;
- status do envio;
- data e horário do envio;
- Admin responsável pelo envio.

Essa entidade pode ser útil para histórico, auditoria e comprovação de que o resultado foi encaminhado à equipe responsável.

## 10. Relações entre entidades

### 10.1 Usuários e Alunos

Um usuário do tipo aluno possui um registro correspondente na entidade Alunos.

Relação:

- um usuário pode estar vinculado a um aluno;
- um aluno pertence a um usuário.

Essa relação permite separar dados de autenticação dos dados escolares.

### 10.2 Turmas e Alunos

Uma turma pode conter vários alunos.

Relação:

- uma turma possui vários alunos;
- um aluno pertence a uma turma.

Essa relação permite agrupar respostas e gerar relatórios por turma.

### 10.3 Refeições e Intenções Alimentares

Uma refeição pode receber várias intenções alimentares.

Relação:

- uma refeição possui várias intenções;
- uma intenção pertence a uma refeição.

Essa relação permite calcular quantos alunos pretendem consumir determinada refeição.

### 10.4 Alunos e Intenções Alimentares

Um aluno pode responder várias refeições ao longo do tempo, mas apenas uma vez para cada refeição específica.

Relação:

- um aluno possui várias intenções alimentares;
- uma intenção pertence a um aluno.

Regra importante:

- não deve existir mais de uma intenção alimentar do mesmo aluno para a mesma refeição.

### 10.5 Refeições e Resumos de Envio

Uma refeição pode possuir um ou mais registros de resumo ou envio.

Relação:

- uma refeição pode gerar um resumo;
- um resumo pertence a uma refeição.

Essa relação permite manter histórico de comunicações enviadas à cozinha.

## 11. Fluxo operacional do sistema

### 11.1 Fluxo do Admin

1. Admin acessa o sistema.
2. Admin cadastra turmas.
3. Admin cadastra alunos vinculados às turmas.
4. Admin cadastra a refeição do dia.
5. Admin define o horário limite para respostas.
6. Sistema disponibiliza a enquete aos alunos.
7. Admin acompanha os dados consolidados.
8. Após o fechamento, Admin gera o resumo.
9. Admin encaminha o resumo à cozinha.

### 11.2 Fluxo do Aluno

1. Aluno acessa o sistema com RM e senha.
2. Sistema valida o usuário.
3. Sistema verifica se é o primeiro acesso do aluno (campo `senha_alterada`).
4. Se for o primeiro acesso, o aluno é redirecionado obrigatoriamente para a tela de criação de senha antes de prosseguir.
5. Após definir a senha, ou caso não seja o primeiro acesso, o sistema exibe a refeição disponível.
6. Aluno informa se pretende ou não consumir a refeição.
7. Sistema registra a resposta.
8. Aluno recebe a confirmação.
9. Caso a enquete ainda esteja aberta, o aluno pode alterar sua resposta.

### 11.3 Fluxo da Cozinha

1. Cozinha recebe o resumo da enquete, preferencialmente via WhatsApp.
2. Cozinha visualiza a quantidade estimada de alunos que pretendem comer.
3. Cozinha utiliza os dados como apoio para planejar a produção da refeição.
4. Caso necessário, a coordenação pode registrar posteriormente informações sobre sobras ou falta de alimento.

## 12. Dados consolidados esperados

O sistema deverá permitir que o Admin visualize dados como:

- total de alunos cadastrados;
- total de alunos ativos;
- total de respostas por refeição;
- quantidade de respostas “Sim”;
- quantidade de respostas “Não”;
- quantidade de alunos que não responderam;
- percentual de participação na enquete;
- respostas agrupadas por turma;
- histórico de respostas por data.

O histórico de respostas por data será apresentado na dashboard do Admin na forma de um gráfico, exibindo a quantidade de votos "Sim", "Não" e sem resposta ao longo do tempo. Esse gráfico permite identificar tendências de adesão e variações no comportamento dos alunos entre diferentes refeições.

Esses dados serão utilizados para apoiar a tomada de decisão e permitir avaliação do impacto do sistema.

## 13. Possíveis expansões futuras

Embora o MVP seja focado em alunos e Admin, o sistema pode ser expandido futuramente com novas funcionalidades, como:

- sugestão de quantidade de refeições a preparar, com margem de segurança configurável;
- importação de alunos por planilha;
- registro de quantidade produzida;
- registro de quantidade consumida;
- registro de sobras;
- análise de desperdício por período;
- relatórios mensais;
- previsão baseada em histórico;
- controle por tipo de refeição;
- painel específico para funcionários da cozinha, caso futuramente haja interesse.

Essas funcionalidades não são obrigatórias para o MVP, mas mostram que a estrutura proposta pode evoluir nos próximos anos.

## 14. Tecnologias sugeridas

Conforme orientação inicial, o backend poderá ser desenvolvido utilizando:

- PHP para a lógica do sistema;
- MySQL para o banco de dados;
- phpMyAdmin para administração e visualização do banco;
- servidor local durante o desenvolvimento, como XAMPP, WAMP ou similar.

O foco do backend será:

- autenticação de usuários;
- controle de permissões;
- cadastro e manutenção de dados;
- relacionamento entre entidades;
- validação das regras de negócio;
- geração de consultas e relatórios consolidados.

## 15. Considerações finais

O sistema proposto busca resolver um problema real da rotina escolar: o desperdício de alimentos causado pela falta de previsibilidade na quantidade de alunos que irão consumir a merenda.

Ao utilizar o RM como mecanismo de identificação, o sistema se aproxima da realidade institucional da escola, facilitando a adoção pelos alunos e permitindo maior controle administrativo.

A escolha de manter apenas dois perfis no MVP, Aluno e Admin, reduz a complexidade inicial e torna o projeto mais viável. Ao mesmo tempo, o envio do resumo à cozinha via WhatsApp respeita a preferência dos funcionários, que não demonstraram interesse em acessar diretamente uma plataforma.

Dessa forma, o sistema se apresenta como uma solução simples, escalável e aplicável, com potencial para apoiar a gestão escolar, reduzir desperdícios e gerar dados úteis para decisões futuras.
