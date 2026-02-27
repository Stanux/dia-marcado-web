<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteTitle }} - Acesso Protegido</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        
        .lock-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lock-icon svg {
            width: 32px;
            height: 32px;
            color: #666;
        }
        
        h1 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 32px;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        input[type="password"]:focus {
            outline: none;
            border-color: #f97373;
        }
        
        .error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 8px;
        }
        
        button {
            width: 100%;
            padding: 14px 24px;
            background: #f97373;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        button:hover {
            background: #C27A92;
        }
        
        button:active {
            transform: translateY(1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="lock-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        
        <h1>{{ $siteTitle }}</h1>
        <p class="subtitle">Este site é protegido por senha. Digite a senha para acessar.</p>
        
        <form method="POST" action="{{ route('public.site.authenticate', ['slug' => $slug]) }}">
            @csrf
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Digite a senha"
                    required
                    autofocus
                >
                @error('password')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            
            <button type="submit">Acessar</button>
        </form>
    </div>
</body>
</html>
