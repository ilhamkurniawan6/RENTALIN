(function attachRentalData(global) {
  const rentalItems = [
    {
      id: "1",
      name: "Kamera Sony A7 III",
      category: "Kamera",
      pricePerDay: 250000,
      image:
        "https://images.unsplash.com/photo-1771218829741-dc952c9e57b1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxtb2Rlcm4lMjBjYW1lcmElMjBlcXVpcG1lbnR8ZW58MXx8fHwxNzczNDgyODkxfDA&ixlib=rb-4.1.0&q=80&w=1080",
      description:
        "Kamera mirrorless full-frame profesional sempurna untuk proyek fotografi dan produksi video. Termasuk dua baterai dan kartu SD 64GB.",
      owner: {
        name: "Sarah Putri",
        avatar:
          "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop",
        rating: 4.8,
      },
      location: "Kampus Utara",
      availability: true,
    },
    {
      id: "2",
      name: "MacBook Pro 16\"",
      category: "Elektronik",
      pricePerDay: 300000,
      image:
        "https://images.unsplash.com/flagged/photo-1576697010739-6373b63f3204?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsYXB0b3AlMjBjb21wdXRlciUyMGRlc2t8ZW58MXx8fHwxNzczNTE2NDEyfDA&ixlib=rb-4.1.0&q=80&w=1080",
      description:
        "Laptop berkinerja tinggi ideal untuk editing video, coding, dan desain grafis. Chip M1 Pro dengan RAM 32GB.",
      owner: {
        name: "Michael Wijaya",
        avatar:
          "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop",
        rating: 5.0,
      },
      location: "Perpustakaan Pusat",
      availability: true,
    },
    {
      id: "3",
      name: "Mini Proyektor",
      category: "Elektronik",
      pricePerDay: 150000,
      image:
        "https://images.unsplash.com/photo-1764193983830-3de9d880ffcb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwb3J0YWJsZSUyMHByb2plY3RvciUyMGRldmljZXxlbnwxfHx8fDE3NzM1ODI3MDJ8MA&ixlib=rb-4.1.0&q=80&w=1080",
      description:
        "Proyektor portable sempurna untuk presentasi dan nonton film. Resolusi 1080p dengan konektivitas HDMI dan wireless.",
      owner: {
        name: "Emma Lestari",
        avatar:
          "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop",
        rating: 4.5,
      },
      location: "Gedung Teknik",
      availability: true,
    },
    {
      id: "4",
      name: "Tripod Profesional",
      category: "Kamera",
      pricePerDay: 80000,
      image:
        "https://images.unsplash.com/photo-1762592818521-1dbbea6139a8?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjYW1lcmElMjB0cmlwb2QlMjBwaG90b2dyYXBoeXxlbnwxfHx8fDE3NzM1ODI3MDJ8MA&ixlib=rb-4.1.0&q=80&w=1080",
      description:
        "Tripod aluminium kokoh dengan fluid head. Dapat diperpanjang hingga 6 kaki. Bagus untuk video dan fotografi.",
      owner: {
        name: "James Santoso",
        avatar:
          "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop",
        rating: 4.7,
      },
      location: "Pusat Seni",
      availability: true,
    },
    {
      id: "5",
      name: "Drone DJI Mini 3 Pro",
      category: "Kamera",
      pricePerDay: 350000,
      image:
        "https://images.unsplash.com/photo-1770411034013-e6cb865ed21a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxkcm9uZSUyMHF1YWRjb3B0ZXIlMjBhZXJpYWx8ZW58MXx8fHwxNzczNTgyNzAzfDA&ixlib=rb-4.1.0&q=80&w=1080",
      description:
        "Drone kompak dengan kamera 4K dan obstacle avoidance. Sempurna untuk proyek fotografi dan videografi aerial.",
      owner: {
        name: "Alex Rivera",
        avatar:
          "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop",
        rating: 4.9,
      },
      location: "Kampus Selatan",
      availability: false,
    },
    {
      id: "6",
      name: "Speaker Bluetooth JBL",
      category: "Elektronik",
      pricePerDay: 100000,
      image:
        "https://images.unsplash.com/photo-1645020089957-608f1f0dfb61?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxibHVldG9vdGglMjBzcGVha2VyJTIwYXVkaW98ZW58MXx8fHwxNzczNTIzMzEzfDA&ixlib=rb-4.1.0&q=80&w=1080",
      description:
        "Speaker bluetooth portable dengan daya tahan baterai 12 jam. Tahan air dan sempurna untuk acara dan gathering.",
      owner: {
        name: "Lisa Andini",
        avatar:
          "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&h=100&fit=crop",
        rating: 4.6,
      },
      location: "Student Union",
      availability: true,
    },
    {
      id: "7",
      name: "Mikrofon Blue Yeti",
      category: "Elektronik",
      pricePerDay: 120000,
      image:
        "https://images.unsplash.com/photo-1613412207572-5bf376466f93?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxtaWNyb3Bob25lJTIwcmVjb3JkaW5nJTIwc3R1ZGlvfGVufDF8fHx8MTc3MzU4MjcwNHww&ixlib=rb-4.1.0&q=80&w=1080",
      description:
        "Mikrofon USB profesional untuk podcasting, streaming, dan rekaman suara. Kualitas audio yang sangat jernih.",
      owner: {
        name: "David Kurniawan",
        avatar:
          "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=100&h=100&fit=crop",
        rating: 4.8,
      },
      location: "Lab Media",
      availability: true,
    },
    {
      id: "8",
      name: "iPad Pro 12.9\"",
      category: "Peralatan Kuliah",
      pricePerDay: 200000,
      image:
        "https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=1080",
      description:
        "iPad Pro terbaru dengan Apple Pencil. Sempurna untuk mencatat, seni digital, dan presentasi.",
      owner: {
        name: "Sophie Tania",
        avatar:
          "https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=100&h=100&fit=crop",
        rating: 5.0,
      },
      location: "Sekolah Bisnis",
      availability: true,
    },
  ];

  const categories = [
    { name: "Kamera", icon: "Camera", count: 15 },
    { name: "Elektronik", icon: "Laptop", count: 28 },
    { name: "Peralatan Kuliah", icon: "BookOpen", count: 12 },
    { name: "Lainnya", icon: "Package", count: 8 },
  ];

  global.RENTAL_DATA = { rentalItems, categories };
})(window);
