# Correção: Erro 413 Request Entity Too Large

## Problema

Ao tentar fazer upload de imagens na Galeria de Mídias, o erro **413 Request Entity Too Large** era retornado pelo Nginx.

### Causa

O Nginx tem um limite padrão de **1MB** para o tamanho do corpo da requisição (`client_max_body_size`). Quando o usuário tenta fazer upload de arquivos maiores que 1MB, o Nginx rejeita a requisição antes mesmo de chegar ao PHP.

## Solução Implementada

### 1. Configuração do Nginx
**Arquivo**: `docker/nginx/default.conf`

Adicionada a diretiva `client_max_body_size`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    charset utf-8;

    # Aumentar limite de upload para 100MB
    client_max_body_size 100M;
    
    # ... resto da configuração
}
```

### 2. Configuração do PHP
**Arquivo**: `docker/php/php.ini`

Aumentados os limites para consistência:

```ini
[PHP]
memory_limit = 512M           # Aumentado de 256M
upload_max_filesize = 100M    # Aumentado de 64M
post_max_size = 100M          # Aumentado de 64M
max_execution_time = 300
max_input_time = 300          # Adicionado
```

## Limites Configurados

| Configuração | Valor Anterior | Valor Novo | Descrição |
|--------------|----------------|------------|-----------|
| Nginx `client_max_body_size` | 1MB (padrão) | **100MB** | Tamanho máximo da requisição |
| PHP `upload_max_filesize` | 64MB | **100MB** | Tamanho máximo por arquivo |
| PHP `post_max_size` | 64MB | **100MB** | Tamanho máximo do POST |
| PHP `memory_limit` | 256MB | **512MB** | Memória disponível para PHP |
| PHP `max_input_time` | 60s (padrão) | **300s** | Tempo máximo para receber dados |

## Como Aplicar as Mudanças

### Opção 1: Reiniciar containers Docker

```bash
docker-compose down
docker-compose up -d
```

### Opção 2: Recarregar apenas os serviços

```bash
# Recarregar Nginx
docker-compose exec nginx nginx -s reload

# Reiniciar PHP-FPM
docker-compose restart php
```

### Opção 3: Rebuild completo (se necessário)

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## Verificação

Após aplicar as mudanças, você pode verificar se funcionou:

### 1. Verificar configuração do Nginx

```bash
docker-compose exec nginx nginx -T | grep client_max_body_size
```

Deve retornar: `client_max_body_size 100M;`

### 2. Verificar configuração do PHP

```bash
docker-compose exec php php -i | grep upload_max_filesize
docker-compose exec php php -i | grep post_max_size
```

Deve retornar:
```
upload_max_filesize => 100M => 100M
post_max_size => 100M => 100M
```

### 3. Testar upload

1. Acesse a Galeria de Mídias
2. Selecione uma imagem grande (> 1MB)
3. Faça o upload
4. ✅ Deve funcionar sem erro 413

## Limites Recomendados por Tipo de Arquivo

Com os novos limites de **100MB**:

| Tipo | Tamanho Típico | Status |
|------|----------------|--------|
| Foto JPEG (alta qualidade) | 2-10 MB | ✅ Suportado |
| Foto RAW | 20-50 MB | ✅ Suportado |
| Vídeo curto (1-2 min) | 50-100 MB | ✅ Suportado |
| Vídeo longo (> 5 min) | > 100 MB | ⚠️ Pode precisar aumentar |

## Ajustes Futuros (Opcional)

Se precisar suportar vídeos maiores:

1. Aumentar `client_max_body_size` no Nginx (ex: 500M)
2. Aumentar `upload_max_filesize` e `post_max_size` no PHP
3. Aumentar `memory_limit` proporcionalmente
4. Considerar usar upload chunked para arquivos muito grandes

## Notas de Segurança

- ✅ Limite de 100MB é razoável para fotos e vídeos de casamento
- ✅ Validação de tipo de arquivo já implementada no backend
- ✅ Validação de extensões bloqueadas (exe, sh, php, etc.)
- ⚠️ Considerar implementar rate limiting para prevenir abuso
- ⚠️ Monitorar uso de disco e implementar quotas por usuário

## Referências

- [Nginx client_max_body_size](http://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size)
- [PHP upload_max_filesize](https://www.php.net/manual/en/ini.core.php#ini.upload-max-filesize)
- [PHP post_max_size](https://www.php.net/manual/en/ini.core.php#ini.post-max-size)
