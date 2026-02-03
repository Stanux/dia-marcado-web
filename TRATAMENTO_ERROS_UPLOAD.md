# Tratamento de Erros no Upload de Imagens

## 📋 Resumo

O sistema possui tratamento completo de erros em múltiplas camadas durante o upload de imagens.

---

## 🔍 Tipos de Erros Tratados

### 1. **Erros de Validação (Antes do Upload)**

#### Tipo de Arquivo Inválido
- **Quando**: Arquivo não é imagem (JPEG, PNG, GIF) ou vídeo (MP4, QuickTime)
- **Mensagem**: `"[nome]: tipo de arquivo não suportado. Apenas imagens (JPEG, PNG, GIF) e vídeos (MP4, QuickTime) são permitidos."`
- **Ação**: Arquivo é rejeitado antes do upload

#### Tamanho Excedido
- **Quando**: Arquivo maior que 100MB
- **Mensagem**: `"[nome]: arquivo muito grande. O tamanho máximo permitido é 100MB."`
- **Ação**: Arquivo é rejeitado antes do upload

### 2. **Erros Durante o Upload**

#### Erro de Rede
- **Quando**: Conexão falha durante o upload
- **Mensagem**: Mensagem do erro de rede
- **Código**: `UPLOAD_FAILED`

#### Erro de Validação no Backend
- **Quando**: Backend rejeita o arquivo (ex: quota excedida)
- **Mensagem**: Mensagem retornada pelo backend
- **Código**: `VALIDATION_FAILED`

#### Erro Desconhecido
- **Quando**: Qualquer outro erro não previsto
- **Mensagem**: `"Erro desconhecido"`
- **Código**: `UPLOAD_FAILED`

---

## 🎨 Feedback Visual

### Estados do Upload

#### 1. **Uploading (Enviando)**
```
┌─────────────────────────────────────┐
│ 🔄 foto.jpg                         │
│    2.5 MB                           │
│ ████████░░░░░░░░░░░░░░░░░░░░ 35%   │
└─────────────────────────────────────┘
```
- Ícone: Spinner animado (azul/cinza)
- Barra de progresso: Azul, atualiza em tempo real
- Borda: Cinza padrão

#### 2. **Completed (Sucesso)**
```
┌─────────────────────────────────────┐
│ ✅ foto.jpg                         │
│    2.5 MB                           │
│ Concluído                           │
└─────────────────────────────────────┘
```
- Ícone: Check verde
- Fundo: Verde claro
- Borda: Verde
- Texto: "Concluído"
- **Desaparece após 2 segundos**

#### 3. **Failed (Erro)**
```
┌─────────────────────────────────────┐
│ ❌ foto.jpg                         │
│    2.5 MB                           │
│ ⚠️ Erro ao fazer upload             │
│ Falhou                              │
└─────────────────────────────────────┘
```
- Ícone: X vermelho
- Fundo: Vermelho claro
- Borda: Vermelha
- Mensagem de erro: Exibida em vermelho
- Texto: "Falhou"
- **Desaparece após 3 segundos**

---

## 🔄 Fluxo de Tratamento de Erros

### Fluxo Completo

```
┌─────────────────┐
│ Usuário seleciona│
│    arquivos      │
└────────┬─────────┘
         │
         ▼
┌─────────────────┐
│   Validação     │◄─── Tipo de arquivo
│   Frontend      │◄─── Tamanho do arquivo
└────────┬─────────┘
         │
         ├─── ❌ Inválido ──► Notificação de erro
         │                    Arquivo não é enviado
         │
         ▼ ✅ Válido
┌─────────────────┐
│  Inicia Upload  │
│  (FormData)     │
└────────┬─────────┘
         │
         ▼
┌─────────────────┐
│  Progresso      │◄─── Atualiza barra (0-100%)
│  (axios)        │◄─── Mostra spinner
└────────┬─────────┘
         │
         ├─── ❌ Erro ──────► Status: 'failed'
         │                    Mensagem de erro
         │                    Ícone vermelho
         │                    Remove após 3s
         │
         ▼ ✅ Sucesso
┌─────────────────┐
│  Status:        │
│  'completed'    │
└────────┬─────────┘
         │
         ▼
┌─────────────────┐
│ Adiciona à      │
│ galeria         │
│ Remove após 2s  │
└─────────────────┘
```

---

## 💻 Implementação Técnica

### 1. Validação (useMediaUpload.ts)

```typescript
const validateFiles = (files: File[]): ValidationResult => {
  const allowedTypes = [
    'image/jpeg', 'image/png', 'image/gif',
    'video/mp4', 'video/quicktime'
  ];
  const maxSize = 100 * 1024 * 1024; // 100MB
  
  // Valida cada arquivo
  // Retorna: validFiles, invalidFiles, errors
};
```

### 2. Upload com Tratamento de Erro

```typescript
const uploadSingleFile = async (albumId: string, file: File): Promise<Media> => {
  const uploadingFile: UploadingFile = {
    id: generateId(),
    file,
    progress: 0,
    status: 'uploading'
  };
  
  try {
    // Upload com axios
    const response = await axios.post('/admin/media/upload', formData, {
      onUploadProgress: (progressEvent) => {
        // Atualiza progresso
      }
    });
    
    uploadingFile.status = 'completed';
    // Remove após 2s
    
  } catch (error) {
    uploadingFile.status = 'failed';
    uploadingFile.error = errorMessage;
    // Remove após 3s
    throw uploadError;
  }
};
```

### 3. Exibição Visual (UploadArea.vue)

```vue
<div v-for="uploadingFile in uploadingFiles">
  <!-- Ícone baseado no status -->
  <svg v-if="status === 'uploading'">🔄</svg>
  <svg v-else-if="status === 'completed'">✅</svg>
  <svg v-else-if="status === 'failed'">❌</svg>
  
  <!-- Mensagem de erro -->
  <p v-if="status === 'failed' && error">
    {{ error }}
  </p>
  
  <!-- Barra de progresso -->
  <div v-if="status === 'uploading'">
    <div :style="{ width: `${progress}%` }"></div>
  </div>
</div>
```

---

## 🎯 Notificações Toast

Além do feedback inline, o sistema exibe notificações toast:

### Sucesso
```
┌────────────────────────────────────┐
│ ✅ Arquivo enviado com sucesso!    │
└────────────────────────────────────┘
```
- Cor: Verde
- Duração: 5 segundos
- Posição: Canto superior direito

### Erro
```
┌────────────────────────────────────┐
│ ❌ Erro ao fazer upload             │
│    [mensagem detalhada do erro]    │
└────────────────────────────────────┘
```
- Cor: Vermelho
- Duração: 5 segundos
- Posição: Canto superior direito

---

## 📝 Mensagens de Erro Possíveis

### Frontend (Validação)

1. **Tipo inválido**
   - `"foto.jpg: tipo de arquivo não suportado. Apenas imagens (JPEG, PNG, GIF) e vídeos (MP4, QuickTime) são permitidos."`

2. **Tamanho excedido**
   - `"video.mp4: arquivo muito grande. O tamanho máximo permitido é 100MB."`

3. **Múltiplos erros**
   - Lista com todos os arquivos inválidos e seus respectivos erros

### Backend (Upload)

1. **Quota excedida**
   - `"Limite de armazenamento excedido"`

2. **Erro de processamento**
   - `"Erro ao processar imagem"`

3. **Erro de permissão**
   - `"Você não tem permissão para fazer upload neste álbum"`

4. **Erro genérico**
   - `"Erro ao fazer upload"`

---

## ✅ Comportamento Esperado

### Cenário 1: Arquivo Válido
1. Usuário seleciona arquivo
2. Validação passa ✅
3. Upload inicia
4. Barra de progresso atualiza (0% → 100%)
5. Status muda para "completed"
6. Notificação verde de sucesso
7. Foto aparece na galeria
8. Item de upload desaparece após 2s

### Cenário 2: Arquivo Inválido (Tipo)
1. Usuário seleciona arquivo .pdf
2. Validação falha ❌
3. Notificação vermelha com erro
4. Arquivo não é enviado
5. Nenhum item de upload aparece

### Cenário 3: Erro Durante Upload
1. Usuário seleciona arquivo válido
2. Validação passa ✅
3. Upload inicia
4. Barra de progresso atualiza
5. Erro ocorre (ex: conexão perdida) ❌
6. Status muda para "failed"
7. Ícone vermelho aparece
8. Mensagem de erro exibida
9. Notificação vermelha
10. Item de upload desaparece após 3s

### Cenário 4: Múltiplos Arquivos (Alguns Inválidos)
1. Usuário seleciona 5 arquivos (3 válidos, 2 inválidos)
2. Validação identifica os 2 inválidos
3. Notificação vermelha lista os 2 erros
4. Apenas os 3 válidos são enviados
5. 3 barras de progresso aparecem
6. Uploads processam em paralelo

---

## 🔧 Melhorias Futuras (Sugestões)

### 1. Botão de Retry
- Adicionar botão "Tentar Novamente" em uploads falhados
- Permitir reenvio sem precisar selecionar o arquivo novamente

### 2. Cancelar Upload
- Botão "X" para cancelar upload em andamento
- Implementar AbortController no axios

### 3. Fila de Upload
- Limitar uploads simultâneos (ex: máximo 3 por vez)
- Enfileirar demais uploads

### 4. Preview Antes do Upload
- Mostrar thumbnail da imagem antes de enviar
- Permitir remover da fila antes do upload

### 5. Resumo de Erros
- Modal com lista completa de erros quando múltiplos uploads falham
- Opção de exportar log de erros

---

## 📊 Resumo Visual

| Status | Ícone | Cor | Duração | Ação |
|--------|-------|-----|---------|------|
| Uploading | 🔄 Spinner | Azul | Até completar | Mostra progresso |
| Completed | ✅ Check | Verde | 2 segundos | Remove automaticamente |
| Failed | ❌ X | Vermelho | 3 segundos | Mostra erro, remove |

---

## 🎓 Conclusão

O sistema possui tratamento robusto de erros com:
- ✅ Validação antes do upload
- ✅ Feedback visual em tempo real
- ✅ Mensagens de erro claras e específicas
- ✅ Notificações toast
- ✅ Remoção automática de itens completados/falhados
- ✅ Suporte a múltiplos uploads simultâneos
- ✅ Tratamento de erros de rede e backend

O usuário sempre sabe o que está acontecendo e por que um upload falhou! 🚀
