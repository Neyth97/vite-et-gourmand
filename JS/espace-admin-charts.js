const menus = [
  'Menu Noël Prestige',
  'Menu Classique Entreprise',
  'Menu Pâques Végétarien',
  'Menu Événement Prestige'
];

// Graphique : commandes par menu
new Chart(document.getElementById('chart-commandes'), {
  type: 'bar',
  data: {
    labels: menus,
    datasets: [{
      label: 'Nombre de commandes',
      data: [42, 31, 18, 27],
      backgroundColor: [
        'rgba(26, 26, 26, 0.8)',
        'rgba(26, 26, 26, 0.6)',
        'rgba(26, 26, 26, 0.4)',
        'rgba(26, 26, 26, 0.2)'
      ],
      borderColor: '#1a1a1a',
      borderWidth: 1,
      borderRadius: 4
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { stepSize: 5 }
      }
    }
  }
});

// Graphique : chiffre d'affaires par mois
new Chart(document.getElementById('chart-ca'), {
  type: 'bar',
  data: {
    labels: ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'],
    datasets: [{
      label: 'Chiffre d\'affaires (€)',
      data: [1200, 980, 1540, 2100, 3200, 1800, 900, 750, 1650, 2400, 4100, 5800],
      backgroundColor: 'rgba(26, 26, 26, 0.75)',
      borderColor: '#1a1a1a',
      borderWidth: 1,
      borderRadius: 4
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ctx.parsed.y.toLocaleString('fr-FR') + ' €'
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: val => val.toLocaleString('fr-FR') + ' €'
        }
      }
    }
  }
});
