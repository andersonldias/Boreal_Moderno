import './globals.css';

export const metadata = {
  title: 'Boreal Moderno - Gestão de Esquadrias',
  description: 'Aplicação moderna para gestão de obras e instalações.',
};

export default function RootLayout({ children }) {
  return (
    <html lang="pt-BR">
      <head>
        <link 
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" 
          rel="stylesheet"
        />
      </head>
      <body>{children}</body>
    </html>
  );
}
